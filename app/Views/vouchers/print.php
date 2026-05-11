<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; }

  .voucher-page {
    width: 210mm;
    height: 297mm;
    page-break-after: always;
  }
  .voucher-page:last-child { page-break-after: auto; }

  .voucher-slot {
    display: block;
    width: 210mm;
    height: 99mm;
    <?php if (!empty($bgB64)): ?>
    background-image: url('<?= $bgB64 ?>');
    <?php endif ?>
    background-size: 100% 100%;
    background-position: top left;
    background-repeat: no-repeat;
  }

  .vt {
    width: 210mm;
    height: 99mm;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11pt;
    font-family: Arial, sans-serif;
    color: #111;
  }
  .vt td { border: none; padding: 0; overflow: hidden; }
</style>
</head>
<body>

<?php
$y_voucher = 80;
$y_recip   = 150;
$y_school  = 160;

$x_vno    = 47;
$x_date   = 165;
$x_recip  = 52;
$x_school = 55;

$h1 = $y_voucher;
$h2 = $y_recip  - $y_voucher;
$h3 = $y_school - $y_recip;
$h4 = 99        - $y_school;

$cA = $x_vno;
$cB = $x_date - $x_vno;
$cC = 210 - $x_date;

$pad_recip  = $x_recip  - $x_vno;
$pad_school = $x_school - $x_vno;

$chunks = array_chunk($vouchers, 3);
foreach ($chunks as $chunk):
    while (count($chunk) < 3) { $chunk[] = null; }
?>
<div class="voucher-page">
<?php foreach ($chunk as $v): ?>
  <div class="voucher-slot">
    <?php if ($v !== null): ?>
    <table class="vt">
      <colgroup>
        <col style="width:<?= $cA ?>mm">
        <col style="width:<?= $cB ?>mm">
        <col style="width:<?= $cC ?>mm">
      </colgroup>
      <tr style="height:<?= $h1 ?>mm"><td colspan="3"></td></tr>
      <tr style="height:<?= $h2 ?>mm">
        <td></td>
        <td style="vertical-align:bottom;"><?= esc($v['voucher_no']) ?></td>
        <td style="vertical-align:bottom;"><?= date('m/d/Y', strtotime($v['voucher_date'] ?? 'now')) ?></td>
      </tr>
      <tr style="height:<?= $h3 ?>mm">
        <td></td>
        <td colspan="2" style="vertical-align:bottom; padding-left:<?= $pad_recip ?>mm;"><?= esc($v['full_name']) ?></td>
      </tr>
      <tr style="height:<?= $h4 ?>mm">
        <td></td>
        <td colspan="2" style="vertical-align:top; padding-left:<?= $pad_school ?>mm;"><?= esc($v['preferred_senior_high_school']) ?></td>
      </tr>
    </table>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

</body>
</html>
