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
            <input id="vmSuffix" name="suffix" type="text" list="dl-suffix" class="vs-input" placeholder="e.g. JR.">
            <datalist id="dl-suffix">
              <option value="JR.">
              <option value="SR.">
              <option value="II">
              <option value="III">
              <option value="IV">
            </datalist>
          </div>

          <div>
            <label class="vs-label" for="vmGender">Sex</label>
            <input id="vmGender" name="gender" type="text" list="dl-gender" class="vs-input" placeholder="MALE / FEMALE">
            <datalist id="dl-gender">
              <option value="MALE">
              <option value="FEMALE">
            </datalist>
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
            <input id="vmJuniorHs" name="junior_high_school" type="text" list="dl-jhs" class="vs-input" placeholder="Type or select a school" required>
            <datalist id="dl-jhs">
              <?php foreach ($juniorHighSchools as $school): ?>
                <option value="<?= esc($school['school_name'] ?? '') ?>">
              <?php endforeach ?>
            </datalist>
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="vmPreferredHs">Preferred Senior High School</label>
            <input id="vmPreferredHs" name="preferred_senior_high_school" type="text" list="dl-shs" class="vs-input" placeholder="Type or select a school" required>
            <datalist id="dl-shs">
              <?php foreach ($seniorHighSchools as $school): ?>
                <option value="<?= esc($school['school_name'] ?? '') ?>">
              <?php endforeach ?>
            </datalist>
          </div>

          <div>
            <label class="vs-label" for="vmRemarks">Remarks</label>
            <input id="vmRemarks" name="remarks_status" type="text" list="dl-remarks" class="vs-input" placeholder="PASSED / FOR REVIEW / FAILED">
            <datalist id="dl-remarks">
              <option value="PASSED">
              <option value="FOR REVIEW">
              <option value="FAILED">
            </datalist>
          </div>

          <!-- Add/Edit mode only: School Year -->
          <div id="vmSchoolYearWrap">
            <label class="vs-label required" for="vmSchoolYear">School Year</label>
            <input id="vmSchoolYear" name="school_year" type="text" list="dl-school-year" class="vs-input" placeholder="e.g. 2025-2026" required>
            <datalist id="dl-school-year">
              <?php foreach (($filterOptions['school_years'] ?? []) as $sy): ?>
                <option value="<?= esc($sy) ?>">
              <?php endforeach ?>
            </datalist>
          </div>

          <div>
            <label class="vs-label" for="vmEligibility">Eligibility</label>
            <input id="vmEligibility" name="eligibility_status" type="text" list="dl-eligibility" class="vs-input" placeholder="eligible / not_eligible">
            <datalist id="dl-eligibility">
              <option value="eligible">
              <option value="not_eligible">
            </datalist>
          </div>
        </div>
      </div>

      <div class="vs-modal-footer">
        <div id="vmLastGeneratedByWrap" class="me-auto" style="display:none;font-size:.8rem;color:#6b7280">
          Last generated by: <strong id="vmLastGeneratedBy">—</strong>
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
