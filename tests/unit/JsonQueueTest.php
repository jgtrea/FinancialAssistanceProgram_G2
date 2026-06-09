<?php

use App\Libraries\JsonPdfQueue;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for the generalized JSON job queue (enqueueChunked + type/payload).
 *
 * The queue is file-backed under WRITEPATH/pdf_queue. To avoid clobbering a
 * developer's real queue while testing, we snapshot the three files in setUp
 * and restore them in tearDown.
 *
 * @internal
 */
final class JsonQueueTest extends CIUnitTestCase
{
    /** @var array<string,string|null> file => original contents (null = didn't exist) */
    private array $backup = [];

    protected function setUp(): void
    {
        parent::setUp();
        JsonPdfQueue::ensureFiles();
        foreach ([JsonPdfQueue::FILE_QUEUE, JsonPdfQueue::FILE_PROCESSING, JsonPdfQueue::FILE_FINISHED] as $f) {
            $path = JsonPdfQueue::path($f);
            $this->backup[$f] = is_file($path) ? file_get_contents($path) : null;
        }
        // Start each test from empty queue files.
        file_put_contents(JsonPdfQueue::path(JsonPdfQueue::FILE_QUEUE), json_encode(['next_job_id' => 1, 'jobs' => []]));
        file_put_contents(JsonPdfQueue::path(JsonPdfQueue::FILE_PROCESSING), json_encode(['jobs' => []]));
        file_put_contents(JsonPdfQueue::path(JsonPdfQueue::FILE_FINISHED), json_encode(['jobs' => []]));
    }

    protected function tearDown(): void
    {
        foreach ($this->backup as $f => $contents) {
            $path = JsonPdfQueue::path($f);
            if ($contents === null) {
                @unlink($path);
            } else {
                file_put_contents($path, $contents);
            }
        }
        parent::tearDown();
    }

    public function testEnqueueChunkedCreatesParentAndChunksWithTypeAndPayload(): void
    {
        $parentId = JsonPdfQueue::enqueueChunked('archive', [1, 2, 3, 4, 5], 7, 2, ['reason' => 'cleanup']);

        $queue = JsonPdfQueue::read(JsonPdfQueue::FILE_QUEUE);
        $jobs  = $queue['jobs'];

        // 1 parent + ceil(5/2) = 3 chunks = 4 records.
        $this->assertCount(4, $jobs);

        $parent = array_values(array_filter($jobs, static fn ($j) => empty($j['parent_job_id'])))[0];
        $chunks = array_values(array_filter($jobs, static fn ($j) => ! empty($j['parent_job_id'])));

        $this->assertSame($parentId, (int) $parent['job_id']);
        $this->assertSame('archive', $parent['type']);
        $this->assertSame(3, (int) $parent['total_chunks']);
        // Parent deliberately does NOT carry the full id list (the chunks do) —
        // storing all N ids on the parent bloated queue.json on large batches.
        $this->assertSame([], $parent['voucher_ids']);
        $this->assertSame(['reason' => 'cleanup'], $parent['payload']);
        $this->assertSame('pending', $parent['status']);

        $this->assertCount(3, $chunks);
        foreach ($chunks as $chunk) {
            $this->assertSame('archive', $chunk['type']);
            $this->assertSame($parentId, (int) $chunk['parent_job_id']);
            // Payload is copied onto every chunk so an isolated worker has the reason.
            $this->assertSame(['reason' => 'cleanup'], $chunk['payload']);
        }

        // Chunk ids partition the parent's ids in order: [1,2],[3,4],[5].
        usort($chunks, static fn ($a, $b) => $a['chunk_index'] <=> $b['chunk_index']);
        $this->assertSame([1, 2], $chunks[0]['voucher_ids']);
        $this->assertSame([3, 4], $chunks[1]['voucher_ids']);
        $this->assertSame([5], $chunks[2]['voucher_ids']);
    }

    public function testEnqueueJobBackCompatTypesAsPdf(): void
    {
        $parentId = JsonPdfQueue::enqueueJob([10, 11], 3, 50);

        $found = JsonPdfQueue::findJob($parentId);
        $this->assertNotNull($found);
        $this->assertSame('pdf', $found['job']['type']);
    }
}
