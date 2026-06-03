<!-- Voucher Add/View/Edit modal -->
<div class="vs-modal-overlay" id="voucherModal" style="display:none">
  <div class="vs-modal" style="max-width:780px">
    <div class="vs-modal-header">
      <h5 id="voucherModalTitle">Add Voucher</h5>
      <button class="vs-modal-close" id="voucherModalClose">&times;</button>
    </div>
    <form id="voucherModalForm" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="student_id" id="vmStudentId" value="">

      <div class="vs-modal-body">
        <div id="voucherModalAlert"></div>

        <div class="vs-form-grid vs-form-grid-4">

          <!-- View mode only: Voucher No. -->
          <div id="vmVoucherNoWrap" style="display:none">
            <label class="vs-label">Voucher No.</label>
            <div id="vmVoucherNoDisplay" class="vs-input" style="background:#f9fafb;min-height:38px;display:flex;align-items:center;cursor:default">—</div>
          </div>

          <!-- Add/Edit mode only: Voucher Date -->
          <div id="vmVoucherDateWrap">
            <label class="vs-label required" for="vmVoucherDate">Voucher Date</label>
            <input id="vmVoucherDate" name="voucher_date" type="date" class="vs-input" required>
          </div>

          <div>
            <label class="vs-label required" for="vmFirstName">First Name</label>
            <input id="vmFirstName" name="first_name" type="text" class="vs-input vs-uppercase" required>
          </div>

          <div>
            <label class="vs-label" for="vmMiddleName">Middle Name</label>
            <input id="vmMiddleName" name="middle_name" type="text" class="vs-input vs-uppercase">
          </div>

          <div>
            <label class="vs-label required" for="vmLastName">Last Name</label>
            <input id="vmLastName" name="last_name" type="text" class="vs-input vs-uppercase" required>
          </div>

          <div>
            <label class="vs-label" for="vmSuffix">Suffix</label>
            <select id="vmSuffix" name="suffix" class="vs-input js-school-select vs-uppercase" data-placeholder="e.g. JR." data-no-search="1">
              <option></option>
              <option value="JR.">JR.</option>
              <option value="SR.">SR.</option>
              <option value="II">II</option>
              <option value="III">III</option>
              <option value="IV">IV</option>
            </select>
          </div>

          <div>
            <label class="vs-label" for="vmGender">Sex</label>
            <select id="vmGender" name="gender" class="vs-input js-school-select" data-placeholder="MALE / FEMALE" data-no-search="1">
              <option></option>
              <option value="MALE">MALE</option>
              <option value="FEMALE">FEMALE</option>
            </select>
          </div>

          <div>
            <label class="vs-label" for="vmGwa">GWA</label>
            <input id="vmGwa" name="gwa" type="number" step="0.01" class="vs-input">
          </div>

          <div>
            <label class="vs-label" for="vmRankNo">Rank No.</label>
            <input id="vmRankNo" name="rank_no" type="number" class="vs-input">
          </div>

          <div>
            <label class="vs-label" for="vmContactNumber">Contact Number</label>
            <input id="vmContactNumber" name="contact_number" type="text" class="vs-input vs-uppercase">
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="vmJuniorHs">Junior High School</label>
            <select id="vmJuniorHs" name="junior_high_school" class="vs-input js-school-select vs-uppercase" data-placeholder="Type or select a school" required>
              <option></option>
              <?php foreach ($juniorHighSchools as $school): ?>
                <option value="<?= esc($school['school_name'] ?? '') ?>"><?= esc($school['school_name'] ?? '') ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="vmPreferredHs">Preferred Senior High School</label>
            <select id="vmPreferredHs" name="preferred_senior_high_school" class="vs-input js-school-select vs-uppercase" data-placeholder="Type or select a school" required>
              <option></option>
              <?php foreach ($seniorHighSchools as $school): ?>
                <option value="<?= esc($school['school_name'] ?? '') ?>"><?= esc($school['school_name'] ?? '') ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div>
            <label class="vs-label" for="vmRemarks">Remarks</label>
            <select id="vmRemarks" name="remarks_status" class="vs-input js-school-selec vs-uppercase" data-placeholder="PASSED / FOR REVIEW / FAILED" data-no-search="1">
              <option></option>
              <option value="PASSED">PASSED</option>
              <option value="FOR REVIEW">FOR REVIEW</option>
              <option value="FAILED">FAILED</option>
            </select>
          </div>

          <!-- Add/Edit mode only: School Year -->
          <div id="vmSchoolYearWrap">
            <label class="vs-label required" for="vmSchoolYear">School Year</label>
            <input id="vmSchoolYear" name="school_year" type="text" list="vmSchoolYear-list"
                   class="vs-input vs-uppercase" placeholder="e.g. 2025-2026" required autocomplete="off">
            <datalist id="vmSchoolYear-list">
              <?php foreach (($filterOptions['school_years'] ?? []) as $sy): ?>
                <option value="<?= esc($sy) ?>">
              <?php endforeach ?>
            </datalist>
          </div>

          <div>
            <label class="vs-label" for="vmEligibility">Eligibility</label>
            <select id="vmEligibility" name="eligibility_status" class="vs-input js-school-select" data-placeholder="ELIGIBLE / NOT ELIGIBLE" data-no-search="1">
              <option></option>
              <option value="eligible">ELIGIBLE</option>
              <option value="not_eligible">NOT ELIGIBLE</option>
            </select>
          </div>
        </div>
      </div>

      <div class="vs-modal-footer">
        <div id="vmLastGeneratedByWrap" class="vm-generation-summary me-auto" style="display:none">
          <details id="vmGenerationHistoryDetails" class="vm-generation-history">
            <summary>Generation history</summary>
            <div id="vmGenerationHistoryList" class="vm-generation-history-list"></div>
          </details>
        </div>
        <button type="button" class="vs-btn vs-btn-outline" id="voucherModalCancel">Close</button>
        <button type="submit" class="vs-btn vs-btn-primary" id="voucherModalSubmit">
          <span id="vmSubmitText">Save Voucher</span>
          <span id="vmSubmitSpinner" class="vs-spinner" style="display:none"></span>
        </button>
      </div>
    </form>
  </div>
</div>
