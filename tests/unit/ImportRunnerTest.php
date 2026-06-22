<?php

use App\Libraries\ImportRunner;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ImportRunnerTest extends TestCase
{
    public function testImportedRemarksKeepCompleteAndIncompleteInRemarksStatus(): void
    {
        $this->assertSame(['COMPLETE', null], $this->normalizeRemarks('complete'));
        $this->assertSame(['INCOMPLETE', null], $this->normalizeRemarks(' Incomplete '));
    }

    public function testImportedCustomRemarksMoveToOtherRemarks(): void
    {
        $this->assertSame(['OTHERS', 'Missing card'], $this->normalizeRemarks('Missing card'));
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function normalizeRemarks(string $value): array
    {
        $method = new ReflectionMethod(ImportRunner::class, 'normalizeImportedRemarks');
        $method->setAccessible(true);

        return $method->invoke(null, $value);
    }
}
