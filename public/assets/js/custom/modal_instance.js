/* modal_instance.js
 * Central registry for all vs-modal HTML templates.
 * Views declare required modals via data-vs-modals attributes (written by
 * the modal_assets() helper in asset_helper.php).
 * Modals are injected into <body> at DOMContentLoaded.
 */
var ModalInstance = (function () {
    'use strict';

    var _VS   = window.__VS  || {};
    var _csrf = _VS.csrf     || { name: '', hash: '' };
    var _urls = _VS.urls     || {};

    /* ── shared helpers ─────────────────────────── */

    function _csrfInput(id) {
        id = id || '_miCsrf';
        return `<input type="hidden" id="${id}" name="${_csrf.name}" value="${_csrf.hash}">`;
    }

    function _u(key) { return _urls[key] || '#'; }

    /* ── modal templates ────────────────────────── */

    var _tpl = {

        archiveModal: function () {
            return `<div class="vs-modal-overlay" id="archiveModal" style="display:none">
              <div class="vs-modal">
                <div class="vs-modal-header">
                  <h5>Archive Students</h5>
                  <button class="vs-modal-close" id="archiveModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p>You are about to archive <strong id="archiveCount">0</strong> student(s). This will move them to the archive.</p>
                  <label class="vs-label" for="archiveReason">Reason (optional)</label>
                  <input type="text" id="archiveReason" class="vs-input" placeholder="e.g. End of school year">
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-outline" id="archiveModalCancel">Cancel</button>
                  <button class="vs-btn vs-btn-danger" id="archiveConfirm">
                    <span id="archiveBtnText">Confirm Archive</span>
                    <span id="archiveBtnSpinner" class="vs-spinner" style="display:none"></span>
                  </button>
                </div>
              </div>
            </div>`;
        },

        infoModal: function () {
            return `<div class="vs-modal-overlay" id="infoModal" style="display:none">
              <div class="vs-modal" style="max-width:420px">
                <div class="vs-modal-header">
                  <h5 id="infoModalTitle">Notice</h5>
                  <button class="vs-modal-close" id="infoModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p id="infoModalMessage" class="mb-0"></p>
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-primary" id="infoModalOk">OK</button>
                </div>
              </div>
            </div>`;
        },

        bulkAllModal: function () {
            return `<div class="vs-modal-overlay" id="bulkAllModal" style="display:none">
              <div class="vs-modal">
                <div class="vs-modal-header">
                  <h5 id="bulkAllTitle">Confirm</h5>
                  <button class="vs-modal-close" id="bulkAllModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p id="bulkAllMessage">You are about to update <strong id="bulkAllCount">0</strong> student(s) matching the current search/filters.</p>
                  <div id="bulkAllReasonWrap" style="display:none">
                    <label class="vs-label" for="bulkAllReason">Reason (optional)</label>
                    <input type="text" id="bulkAllReason" class="vs-input" placeholder="e.g. End of school year">
                  </div>
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-outline" id="bulkAllCancel">Cancel</button>
                  <button class="vs-btn vs-btn-primary" id="bulkAllConfirm">
                    <span id="bulkAllBtnText">Confirm</span>
                    <span id="bulkAllBtnSpinner" class="vs-spinner" style="display:none"></span>
                  </button>
                </div>
              </div>
            </div>`;
        },

        importModal: function () {
            return `<div class="vs-modal-overlay" id="importModal" style="display:none">
              <div class="vs-modal">
                <div class="vs-modal-header">
                  <h5>Import Students</h5>
                  <button class="vs-modal-close" id="importModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p class="text-muted small mb-3">
                    Upload an <strong>.xlsx</strong>, <strong>.xls</strong>, or <strong>.csv</strong> file.<br>
                    Columns must be in this exact order:
                    <em>Voucher No., Voucher Date, Full Name, Rank No., GWA, Sex, Junior High School, Preferred Senior High School, Contact Number, Remarks</em>
                  </p>
                  <label class="vs-label" for="importFile">File</label>
                  <input type="file" id="importFile" class="vs-input" accept=".xlsx,.xls,.csv">
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-outline" id="importModalCancel">Cancel</button>
                  <button class="vs-btn vs-btn-primary" id="importConfirm">
                    <span id="importBtnText">Import</span>
                    <span id="importBtnSpinner" class="vs-spinner" style="display:none"></span>
                  </button>
                </div>
              </div>
            </div>`;
        },

        filterModal: function () {
            return `<div class="vs-modal-overlay" id="filterModal" style="display:none">
              <div class="vs-modal" style="max-width:680px">
                <div class="vs-modal-header">
                  <h5>Advanced Filters</h5>
                  <button class="vs-modal-close" id="filterModalClose">&times;</button>
                </div>  
                <div class="vs-modal-body">
                  <div class="vs-form-grid vs-form-grid-4">
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterSchoolYear">School Year</label>
                      <select id="filterSchoolYear" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterGender">Sex</label>
                      <select id="filterGender" class="vs-input js-filter-select" data-placeholder="MALE / FEMALE" data-no-search="1">
                        <option></option><option value="MALE">MALE</option><option value="FEMALE">FEMALE</option>
                      </select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterRemarks">Remarks</label>
                      <select id="filterRemarks" class="vs-input js-filter-select" data-placeholder="PASSED / FOR REVIEW / FAILED" data-no-search="1">
                        <option></option><option value="PASSED">PASSED</option><option value="FOR REVIEW">FOR REVIEW</option><option value="FAILED">FAILED</option>
                      </select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterVoucherStatus">Voucher Status</label>
                      <select id="filterVoucherStatus" class="vs-input js-filter-select" data-placeholder="GENERATED / NOT GENERATED" data-no-search="1">
                        <option></option><option value="generated">generated</option><option value="not_generated">not_generated</option>
                      </select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterDateFrom">Voucher Date From</label>
                      <input type="date" id="filterDateFrom" class="vs-input">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterDateTo">Voucher Date To</label>
                      <input type="date" id="filterDateTo" class="vs-input">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterJuniorHs">Junior High School</label>
                      <select id="filterJuniorHs" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterPreferredHs">Preferred Senior HS</label>
                      <select id="filterPreferredHs" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterGwaMin">GWA Min</label>
                      <input type="number" step="0.01" id="filterGwaMin" class="vs-input" placeholder="e.g. 80">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterGwaMax">GWA Max</label>
                      <input type="number" step="0.01" id="filterGwaMax" class="vs-input" placeholder="e.g. 100">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="filterEligibility">Eligibility Status</label>
                      <select id="filterEligibility" class="vs-input js-filter-select" data-placeholder="ELIGIBLE / NOT ELIGIBLE" data-no-search="1">
                        <option></option><option value="eligible">eligible</option><option value="not_eligible">not_eligible</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="vs-modal-footer">
                  <button type="button" class="vs-btn vs-btn-outline" id="filterClear">Clear All</button>
                  <button type="button" class="vs-btn vs-btn-outline" id="filterModalCancel">Cancel</button>
                  <button type="button" class="vs-btn vs-btn-primary" id="filterApply">Apply Filters</button>
                </div>
              </div>
            </div>`;
        },

        exportVoucher: function () {
            return `<div class="vs-modal-overlay" id="exportModal" style="display:none">
              <div class="vs-modal">
                <div class="vs-modal-header">
                  <h5>Export Students</h5>
                  <button class="vs-modal-close" id="exportModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p>Choose the file format to export the selected student records.</p>
                  <div class="d-flex gap-3 mt-3">
                    <a href="${_u('voucherExport')}?format=xlsx" data-export-format="xlsx" class="vs-btn vs-btn-outline flex-fill text-center">Excel (.xlsx)</a>
                    <a href="${_u('voucherExport')}?format=csv" data-export-format="csv" class="vs-btn vs-btn-outline flex-fill text-center">CSV (.csv)</a>
                  </div>
                </div>
              </div>
            </div>`;
        },

        voucherModal: function () {
            return `<div class="vs-modal-overlay" id="voucherModal" style="display:none">
              <div class="vs-modal" style="max-width:780px">
                <div class="vs-modal-header">
                  <h5 id="voucherModalTitle">Add Voucher</h5>
                  <button class="vs-modal-close" id="voucherModalClose">&times;</button>
                </div>
                <form id="voucherModalForm" novalidate>
                  ${_csrfInput('vmCsrf')}
                  <input type="hidden" name="student_id" id="vmStudentId" value="">
                  <div class="vs-modal-body">
                    <div id="voucherModalAlert"></div>
                    <div class="vs-form-grid vs-form-grid-4">
                      <div id="vmVoucherNoWrap" class="vs-span-4" style="display:none">
                        <label class="vs-label">Voucher No.</label>
                        <div id="vmVoucherNoDisplay" class="vs-input" style="background:#f9fafb;min-height:38px;display:flex;align-items:center;cursor:default;max-width:220px">—</div>
                      </div>
                      <div id="vmVoucherDateWrap" class="vs-span-4">
                        <label class="vs-label required" for="vmVoucherDate">Voucher Date</label>
                        <input id="vmVoucherDate" name="voucher_date" type="date" class="vs-input" required style="max-width:220px">
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
                      <div></div>
                      <div>
                        <label class="vs-label" for="vmSuffix">Suffix</label>
                        <select id="vmSuffix" name="suffix" class="vs-input js-school-select vs-uppercase" data-placeholder="- SELECT -" data-no-search="1">
                          <option></option><option value="JR.">JR.</option><option value="SR.">SR.</option>
                          <option value="II">II</option><option value="III">III</option><option value="IV">IV</option>
                        </select>
                      </div>
                      <div>
                        <label class="vs-label" for="vmGender">Sex</label>
                        <select id="vmGender" name="gender" class="vs-input js-school-select" data-placeholder="MALE / FEMALE" data-no-search="1">
                          <option></option><option value="MALE">MALE</option><option value="FEMALE">FEMALE</option>
                        </select>
                      </div>
                      <div>
                        <label class="vs-label" for="vmContactNumber">Contact Number</label>
                        <input id="vmContactNumber" name="contact_number" type="text" class="vs-input vs-uppercase">
                      </div>
                      <div></div>
                      <div>
                        <label class="vs-label" for="vmGwa">GWA</label>
                        <input id="vmGwa" name="gwa" type="number" step="0.01" class="vs-input">
                      </div>
                      <div>
                        <label class="vs-label" for="vmRankNo">Rank No.</label>
                        <input id="vmRankNo" name="rank_no" type="number" class="vs-input">
                      </div>
                      <div></div><div></div>
                      <div>
                        <label class="vs-label required" for="vmJuniorHs">Junior High School</label>
                        <select id="vmJuniorHs" name="junior_high_school" class="vs-input js-school-select vs-uppercase" data-placeholder="- TYPE OR SELECT -" required><option></option></select>
                      </div>
                      <div>
                        <label class="vs-label required" for="vmPreferredHs">Preferred Senior High School</label>
                        <select id="vmPreferredHs" name="preferred_senior_high_school" class="vs-input js-school-select vs-uppercase" data-placeholder="- TYPE OR SELECT -" required><option></option></select>
                      </div>
                      <div id="vmSchoolYearWrap">
                        <label class="vs-label required" for="vmSchoolYear">School Year</label>
                        <input id="vmSchoolYear" name="school_year" type="text" list="vmSchoolYear-list" class="vs-input vs-uppercase" placeholder="e.g. 2025-2026" required autocomplete="off">
                        <datalist id="vmSchoolYear-list"></datalist>
                      </div>
                      <div></div>
                      <div>
                        <label class="vs-label required" for="vmEligibility">Eligibility</label>
                        <select id="vmEligibility" name="eligibility_status" class="vs-input js-school-select" data-placeholder="ELIGIBLE / NOT ELIGIBLE" data-no-search="1" required>
                          <option></option><option value="eligible">ELIGIBLE</option><option value="not_eligible">NOT ELIGIBLE</option>
                        </select>
                      </div>
                      <div>
                        <label class="vs-label required" for="vmRemarks">Remarks</label>
                        <select id="vmRemarks" name="remarks_status" class="vs-input js-school-select vs-uppercase" data-placeholder="PASSED / FOR REVIEW / FAILED" data-no-search="1" required>
                          <option></option><option value="PASSED">PASSED</option><option value="FOR REVIEW">FOR REVIEW</option><option value="FAILED">FAILED</option>
                        </select>
                      </div>
                      <div></div><div></div>
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
            </div>`;
        },

        userModal: function () {
            return `<div class="vs-modal-overlay" id="userModal" style="display:none">
              <div class="vs-modal" style="max-width:680px">
                <div class="vs-modal-header">
                  <h5 id="userModalTitle">Add User</h5>
                  <button class="vs-modal-close" id="userModalClose">&times;</button>
                </div>
                <form id="userModalForm" novalidate>
                  ${_csrfInput('umCsrf')}
                  <input type="hidden" name="user_id" id="umUserId" value="">
                  <div class="vs-modal-body">
                    <div id="userModalAlert"></div>
                    <div class="vs-form-grid vs-form-grid-4">
                      <div>
                        <label class="vs-label required" for="umFirstName">First Name</label>
                        <input type="text" id="umFirstName" name="first_name" class="vs-input vs-uppercase" required spellcheck="false">
                      </div>
                      <div>
                        <label class="vs-label" for="umMiddleName">Middle Name</label>
                        <input type="text" id="umMiddleName" name="middle_name" class="vs-input vs-uppercase" spellcheck="false">
                      </div>
                      <div>
                        <label class="vs-label required" for="umLastName">Last Name</label>
                        <input type="text" id="umLastName" name="last_name" class="vs-input vs-uppercase" required spellcheck="false">
                      </div>
                      <div></div>
                      <div class="vs-span-2">
                        <label class="vs-label required" for="umUsername">Username <span class="vs-label-hint">(used for login)</span></label>
                        <input type="text" id="umUsername" name="username" class="vs-input" required spellcheck="false" autocomplete="off">
                      </div>
                      <div class="vs-span-2"></div>
                      <div class="vs-span-2">
                        <label class="vs-label required" for="umEmail">Email</label>
                        <input type="email" id="umEmail" name="email" class="vs-input" required autocomplete="email" autocapitalize="none" spellcheck="false">
                      </div>
                      <div class="vs-span-2">
                        <label class="vs-label" id="umPasswordLabel" for="umPassword">Password</label>
                        <input type="password" id="umPassword" name="password" class="vs-input" autocomplete="new-password" autocapitalize="none" spellcheck="false">
                      </div>
                      <div class="vs-span-2">
                        <label class="vs-label required" for="umRole">Role</label>
                        <select id="umRole" name="role" class="vs-input js-filter-select" data-placeholder="ADMIN / USER" data-no-search="1" required>
                          <option></option><option value="admin">ADMIN</option><option value="user">USER</option>
                        </select>
                      </div>
                      <div class="vs-span-2"></div>
                    </div>
                  </div>
                  <div class="vs-modal-footer">
                    <button type="button" class="vs-btn vs-btn-outline" id="userModalCancel">Close</button>
                    <button type="submit" class="vs-btn vs-btn-primary" id="userModalSubmit">
                      <span id="umSubmitText">Save User</span>
                      <span id="umSubmitSpinner" class="vs-spinner" style="display:none"></span>
                    </button>
                  </div>
                </form>
              </div>
            </div>`;
        },

        deactivateModal: function () {
            return `<div class="vs-modal-overlay" id="deactivateModal" style="display:none">
              <div class="vs-modal" style="max-width:460px">
                <div class="vs-modal-header">
                  <h5>Deactivate User</h5>
                  <button class="vs-modal-close" id="deactivateModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p>You are about to deactivate 1 user(s). This will move them to the archive.</p>
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-outline" id="deactivateModalCancel">Cancel</button>
                  <button class="vs-btn vs-btn-danger" id="deactivateModalConfirm">
                    <span id="deactivateBtnText">Deactivate</span>
                    <span id="deactivateBtnSpinner" class="vs-spinner" style="display:none"></span>
                  </button>
                </div>
              </div>
            </div>`;
        },

        archiveCurrentModal: function () {
            return `<div class="vs-modal-overlay" id="archiveCurrentModal" style="display:none">
              <div class="vs-modal" style="max-width:500px">
                <div class="vs-modal-header">
                  <h5 id="archiveCurrentModalTitle">Archive Current Data</h5>
                  <button class="vs-modal-close" id="archiveCurrentModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <div id="archiveCurrentModalBody"></div>
                  <div class="mt-3">
                    <label class="vs-label" for="archiveCurrentReason">Reason (optional)</label>
                    <input type="text" id="archiveCurrentReason" class="vs-input" placeholder="e.g. End of school year">
                  </div>
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-outline" id="archiveCurrentModalCancel">Cancel</button>
                  <button class="vs-btn vs-btn-danger" id="archiveCurrentModalConfirm">
                    <span id="archiveCurrentBtnText">Confirm Archive</span>
                    <span id="archiveCurrentBtnSpinner" class="vs-spinner" style="display:none"></span>
                  </button>
                </div>
              </div>
            </div>`;
        },

        archiveFilterModal: function () {
            return `<div class="vs-modal-overlay" id="archiveFilterModal" style="display:none">
              <div class="vs-modal" style="max-width:680px">
                <div class="vs-modal-header">
                  <h5>Advanced Filters</h5>
                  <button class="vs-modal-close" id="archiveFilterClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <div class="vs-form-grid vs-form-grid-4">
                    <div class="vs-span-2">
                      <label class="vs-label required" for="afSchoolYear">School Year</label>
                      <select id="afSchoolYear" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afGender">Sex</label>
                      <select id="afGender" class="vs-input js-filter-select" data-placeholder="MALE / FEMALE" data-no-search="1">
                        <option></option><option value="MALE">MALE</option><option value="FEMALE">FEMALE</option>
                      </select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afRemarks">Remarks</label>
                      <select id="afRemarks" class="vs-input js-filter-select" data-placeholder="PASSED / FOR REVIEW / FAILED" data-no-search="1">
                        <option></option><option value="PASSED">PASSED</option><option value="FOR REVIEW">FOR REVIEW</option><option value="FAILED">FAILED</option>
                      </select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afVoucherStatus">Voucher Status</label>
                      <select id="afVoucherStatus" class="vs-input js-filter-select" data-placeholder="GENERATED / NOT GENERATED" data-no-search="1">
                        <option></option><option value="generated">generated</option><option value="not_generated">not_generated</option>
                      </select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afDateFrom">Voucher Date From</label>
                      <input type="date" id="afDateFrom" class="vs-input">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afDateTo">Voucher Date To</label>
                      <input type="date" id="afDateTo" class="vs-input">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afJuniorHs">Junior High School</label>
                      <select id="afJuniorHs" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afPreferredHs">Preferred Senior HS</label>
                      <select id="afPreferredHs" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afGwaMin">GWA Min</label>
                      <input type="number" step="0.01" id="afGwaMin" class="vs-input" placeholder="e.g. 80">
                    </div>
                    <div class="vs-span-2">
                      <label class="vs-label" for="afGwaMax">GWA Max</label>
                      <input type="number" step="0.01" id="afGwaMax" class="vs-input" placeholder="e.g. 100">
                    </div>
                  </div>
                </div>
                <div class="vs-modal-footer">
                  <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterClear">Clear All</button>
                  <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterCancel">Cancel</button>
                  <button type="button" class="vs-btn vs-btn-primary" id="archiveFilterApply">Apply Filters</button>
                </div>
              </div>
            </div>`;
        },

        auditFilterModal: function () {
            return `<div class="vs-modal-overlay" id="auditFilterModal" style="display:none">
                <div class="vs-modal" style="max-width:680px">
                    <div class="vs-modal-header">
                        <h5>Advanced Filters</h5>
                        <button class="vs-modal-close" id="auditFilterModalClose">&times;</button>
                    </div>
                    <div class="vs-modal-body">
                        <div class="vs-form-grid vs-form-grid-4">
                            <div class="vs-span-4">
                                <label class="vs-label" for="auditFilterAction">Action</label>
                                <select id="auditFilterAction" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -"><option></option></select>
                            </div>
                            <div class="vs-span-2">
                                <label class="vs-label" for="auditFilterDateFrom">Date From</label>
                                <input type="date" id="auditFilterDateFrom" class="vs-input">
                            </div>
                            <div class="vs-span-2">
                                <label class="vs-label" for="auditFilterDateTo">Date To</label>
                                <input type="date" id="auditFilterDateTo" class="vs-input">
                            </div>
                        </div>
                    </div>
                    <div class="vs-modal-footer">
                        <button type="button" class="vs-btn vs-btn-outline" id="auditFilterClear">Clear All</button>
                        <button type="button" class="vs-btn vs-btn-outline" id="auditFilterModalCancel">Cancel</button>
                        <button type="button" class="vs-btn vs-btn-primary" id="auditFilterApply">Apply Filters</button>
                    </div>
                </div>
            </div>`;
        },

        schoolArchiveModal: function () {
            return `<div class="vs-modal-overlay" id="schoolArchiveModal" style="display:none">
                <div class="vs-modal">
                    <div class="vs-modal-header">
                        <h5>Archive Schools</h5>
                        <button class="vs-modal-close" id="schoolArchiveModalClose">&times;</button>
                    </div>
                    <div class="vs-modal-body">
                        <p>You are about to archive <strong id="schoolArchiveCount">0</strong> school(s).
                           Archived schools will no longer appear in the voucher school picker.</p>
                    </div>
                    <div class="vs-modal-footer">
                        <button class="vs-btn vs-btn-outline" id="schoolArchiveModalCancel">Cancel</button>
                        <button class="vs-btn vs-btn-danger" id="schoolArchiveConfirm">
                            <span id="schoolArchiveBtnText">Confirm Archive</span>
                            <span id="schoolArchiveBtnSpinner" class="vs-spinner" style="display:none"></span>
                        </button>
                    </div>
                </div>
            </div>`;
        },

        schoolExportModal: function () {
            return `<div class="vs-modal-overlay" id="schoolExportModal" style="display:none">
                <div class="vs-modal">
                    <div class="vs-modal-header">
                        <h5>Export Schools</h5>
                        <button class="vs-modal-close" id="schoolExportModalClose">&times;</button>
                    </div>
                    <div class="vs-modal-body">
                        <p>Choose the file format to export the selected school records.</p>
                        <div class="d-flex gap-3 mt-3">
                            <a href="${_u('schoolExport')}?format=excel" id="exportExcelLink" class="vs-btn vs-btn-outline flex-fill text-center">Excel (.xlsx)</a>
                            <a href="${_u('schoolExport')}?format=csv" id="exportCsvLink" class="vs-btn vs-btn-outline flex-fill text-center">CSV (.csv)</a>
                        </div>
                    </div>
                </div>
            </div>`;
        },

        schoolModal: function () {
            return `<div class="vs-modal-overlay" id="schoolModal" style="display:none">
                <div class="vs-modal" style="max-width:480px">
                    <div class="vs-modal-header">
                        <h5 id="schoolModalTitle">Add School</h5>
                        <button class="vs-modal-close" id="schoolModalClose">&times;</button>
                    </div>
                    <form id="schoolModalForm" novalidate>
                        ${_csrfInput('smCsrf')}
                        <input type="hidden" name="school_id" id="smSchoolId" value="">
                        <div class="vs-modal-body">
                            <div id="schoolModalAlert"></div>
                            <div class="vs-form-grid vs-form-grid-4">
                                <div class="vs-span-2">
                                    <label class="vs-label required" for="smSchoolName">School Name</label>
                                    <input id="smSchoolName" name="school_name" type="text" class="vs-input vs-uppercase" required placeholder="e.g. TANDAG NATIONAL HIGH SCHOOL">
                                </div>
                                <div class="vs-span-2">
                                    <label class="vs-label">Acronym</label>
                                    <div id="smAcronymDisplay" class="vs-input" style="background:#f9fafb;cursor:default;display:flex;align-items:center;min-height:38px;color:#6b7280">—</div>
                                </div>
                                <div class="vs-span-4">
                                    <label class="vs-label required" for="smSchoolLevel">Level</label>
                                    <select id="smSchoolLevel" name="school_level" class="vs-input js-filter-select" data-placeholder="JHS / SHS" data-no-search="1" required>
                                        <option></option><option value="JHS">JHS</option><option value="SHS">SHS</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="vs-modal-footer">
                            <button type="button" class="vs-btn vs-btn-outline" id="schoolModalCancel">Cancel</button>
                            <button type="submit" class="vs-btn vs-btn-primary" id="schoolModalSubmit">
                                <span id="smSubmitText">Save</span>
                                <span id="smSubmitSpinner" class="vs-spinner" style="display:none"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
        },

        schoolImportModal: function () {
            return `<div class="vs-modal-overlay" id="schoolImportModal" style="display:none">
                <div class="vs-modal" style="max-width:500px">
                    <div class="vs-modal-header">
                        <h5>Import Schools</h5>
                        <button class="vs-modal-close" id="schoolImportClose">&times;</button>
                    </div>
                    <form id="schoolImportForm" novalidate enctype="multipart/form-data">
                        ${_csrfInput('siCsrf')}
                        <div class="vs-modal-body">
                            <div id="schoolImportAlert"></div>
                            <p class="text-muted" style="font-size:.875rem">
                                Upload a <strong>.csv</strong>, <strong>.xlsx</strong>, or <strong>.xls</strong> file
                                with columns: <strong>School Name</strong> and <strong>Level</strong> (JHS or SHS).
                                Duplicate entries will be skipped automatically.
                            </p>
                            <div class="mt-3">
                                <label class="vs-label" for="schoolFileInput">File</label>
                                <input id="schoolFileInput" name="school_file" type="file" class="vs-input" accept=".csv,.xlsx,.xls" required>
                            </div>
                        </div>
                        <div class="vs-modal-footer">
                            <button type="button" class="vs-btn vs-btn-outline" id="schoolImportCancel">Cancel</button>
                            <button type="submit" class="vs-btn vs-btn-primary" id="schoolImportSubmit">
                                <span id="siSubmitText">Import</span>
                                <span id="siSubmitSpinner" class="vs-spinner" style="display:none"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
        },

        sigArchiveModal: function () {
            return `<div class="vs-modal-overlay" id="sigArchiveModal" style="display:none">
              <div class="vs-modal">
                <div class="vs-modal-header">
                  <h5>Archive Signatories</h5>
                  <button class="vs-modal-close" id="sigArchiveModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p>You are about to archive <strong id="sigArchiveCount">0</strong> signatory(ies). This will move them to the archive.</p>
                </div>
                <div class="vs-modal-footer">
                  <button class="vs-btn vs-btn-outline" id="sigArchiveModalCancel">Cancel</button>
                  <button class="vs-btn vs-btn-danger" id="sigArchiveConfirm">
                    <span id="sigArchiveBtnText">Confirm Archive</span>
                    <span id="sigArchiveBtnSpinner" class="vs-spinner" style="display:none"></span>
                  </button>
                </div>
              </div>
            </div>`;
        },

        signatoryModal: function () {
            var prefixOpts = ['DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'];
            var suffixOpts = ['JR.', 'SR.', 'II', 'III', 'IV', 'V'];
            var degreeOpts = ['None', 'MPA', 'BSc', 'BA', 'Master', 'MSc', 'MA', 'MBA', 'Doctorate', 'PhD', 'MD', 'JD', 'LLB', 'DDS', 'EdD', 'Other'];

            function _staticOpts(arr) {
                return arr.map(function(v) { return `<option value="${v}">${v}</option>`; }).join('');
            }

            return `<div class="vs-modal-overlay" id="signatoryModal" style="display:none">
              <div class="vs-modal" style="max-width:780px">
                <div class="vs-modal-header">
                  <h5 id="signatoryModalTitle">Add Signatory</h5>
                  <button class="vs-modal-close" id="signatoryModalClose">&times;</button>
                </div>
                <form id="signatoryModalForm" novalidate enctype="multipart/form-data">
                  ${_csrfInput('sigCsrf')}
                  <input type="hidden" name="signatory_id" id="smSignatoryId" value="">
                  <div class="vs-modal-body">
                    <div id="signatoryModalAlert"></div>
                    <div class="vs-form-grid vs-form-grid-4">
                      <div>
                        <label class="vs-label required" for="smFirstName">First Name</label>
                        <input id="smFirstName" name="first_name" type="text" class="vs-input vs-uppercase" required>
                      </div>
                      <div>
                        <label class="vs-label" for="smMiddleName">Middle Name</label>
                        <input id="smMiddleName" name="middle_name" type="text" class="vs-input vs-uppercase">
                      </div>
                      <div>
                        <label class="vs-label required" for="smLastName">Last Name</label>
                        <input id="smLastName" name="last_name" type="text" class="vs-input vs-uppercase" required>
                      </div>
                      <div></div>
                      <div>
                        <label class="vs-label" for="smPrefix">Prefix</label>
                        <select id="smPrefix" name="prefix" class="vs-input js-filter-select" data-placeholder="- SELECT -" data-no-search="1">
                          <option></option>${_staticOpts(prefixOpts)}
                        </select>
                      </div>
                      <div>
                        <label class="vs-label" for="smSuffix">Suffix</label>
                        <select id="smSuffix" name="suffix" class="vs-input js-filter-select" data-placeholder="- SELECT -" data-no-search="1">
                          <option></option>${_staticOpts(suffixOpts)}
                        </select>
                      </div>
                      <div></div><div></div>
                      <div>
                        <label class="vs-label" for="smDegree">Degree</label>
                        <select id="smDegree" name="degree" class="vs-input js-filter-select" data-placeholder="- TYPE OR SELECT -">
                          <option></option>${_staticOpts(degreeOpts)}
                        </select>
                        <input id="smDegreeOther" name="degree_other" type="text" class="vs-input mt-2" placeholder="Specify degree" style="display:none">
                      </div>
                      <div class="vs-span-2">
                        <label class="vs-label required" for="smPositionTitle">Position Title</label>
                        <input id="smPositionTitle" name="position_title" type="text" class="vs-input vs-uppercase" required>
                      </div>
                      <div style="grid-column:1 / -1">
                        <label class="vs-label" for="smSignatureImage">Signature Image</label>
                        <input id="smSignatureImage" name="signature_image" type="file" class="vs-input" accept="image/png,image/jpeg,image/jpg,image/webp">
                        <small class="text-muted">PNG, JPG, or WEBP — max 2 MB. Leave empty to keep the current image.</small>
                        <div class="form-check mt-2">
                          <input class="form-check-input" type="checkbox" name="auto_remove_bg" value="1" id="smAutoRemoveBg" checked>
                          <label class="form-check-label" for="smAutoRemoveBg">Remove background automatically (best for signatures on plain white paper)</label>
                        </div>
                        <div id="smCurrentSignatureWrap" class="mt-3" style="display:none">
                          <p class="vs-label mb-1">Current Signature</p>
                          <img id="smCurrentSignaturePreview" src="" alt="Current signature" style="max-height:80px;background:#fff;padding:4px;border:1px solid #ddd;border-radius:4px;">
                          <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_signature" value="1" id="smRemoveSignature">
                            <label class="form-check-label" for="smRemoveSignature">Remove current signature</label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="vs-modal-footer">
                    <button type="button" class="vs-btn vs-btn-outline" id="signatoryModalCancel">Close</button>
                    <button type="submit" class="vs-btn vs-btn-primary" id="signatoryModalSubmit">
                      <span id="smSubmitText">Save</span>
                      <span id="smSubmitSpinner" class="vs-spinner" style="display:none"></span>
                    </button>
                  </div>
                </form>
              </div>
            </div>`;
        },

        pdfStatusModal: function () {
            return `<div class="vs-modal-overlay" id="pdfStatusModal" style="display:none">
              <div class="vs-modal">
                <div class="vs-modal-header">
                  <h5>Voucher Generation Status</h5>
                  <button class="vs-modal-close" id="pdfStatusModalClose">&times;</button>
                </div>
                <div class="vs-modal-body">
                  <p id="pdfStatusEmpty" style="display:none">No recent generation job for this session.</p>
                  <div id="pdfStatusContent" style="display:none">
                    <p><strong>Job #:</strong> <span id="pdfStatusJobId">-</span></p>
                    <p><strong>Status:</strong> <span id="pdfStatusBadge" class="vs-badge">-</span></p>
                    <p id="pdfStatusProgressLine"><strong>Progress:</strong> <span id="pdfStatusProgress">0 / 0</span></p>
                    <p id="pdfStatusErrorLine" style="display:none; color:#b00020"><strong>Error:</strong> <span id="pdfStatusError"></span></p>
                    <div class="mt-3" id="pdfStatusDownloadWrap" style="display:none">
                      <a id="pdfStatusDownload" class="vs-btn vs-btn-blue" href="#">Download Voucher</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>`;
        },

        accountModal: function () {
            return `<div class="vs-modal-overlay" id="accountModal" style="display:none">
                <div class="vs-modal" style="max-width:720px;width:95%">
                    <div class="vs-modal-header">
                        <h5>My Account</h5>
                        <button class="vs-modal-close" id="accountModalClose">&times;</button>
                    </div>
                    <div class="vs-modal-body">
                        <div id="accountModalMsg" class="mb-3" style="display:none"></div>
                        <form id="accountModalForm" autocomplete="off">
                            <input type="hidden" id="amCsrf" name="${_csrf.name}" value="${_csrf.hash}">
                            <div class="vs-form-grid vs-form-grid-4">
                                <div>
                                    <label class="vs-label required">Username</label>
                                    <input id="amUsername" name="username" type="text" class="vs-input" required>
                                </div>
                                <div>
                                    <label class="vs-label required">Email</label>
                                    <input id="amEmail" name="email" type="email" class="vs-input" required>
                                </div>
                                <div>
                                    <label class="vs-label">Role</label>
                                    <div id="amRole" class="vs-input" style="background:#f9fafb;cursor:default"></div>
                                </div>
                                <div></div>
                                <div>
                                    <label class="vs-label required">First Name</label>
                                    <input id="amFirstName" name="first_name" type="text" class="vs-input vs-uppercase" required>
                                </div>
                                <div>
                                    <label class="vs-label">Middle Name</label>
                                    <input id="amMiddleName" name="middle_name" type="text" class="vs-input vs-uppercase">
                                </div>
                                <div>
                                    <label class="vs-label required">Last Name</label>
                                    <input id="amLastName" name="last_name" type="text" class="vs-input vs-uppercase" required>
                                </div>
                                <div></div>
                                <div class="vs-span-4">
                                    <h2 class="vs-section-title">Change Password</h2>
                                </div>
                                <div>
                                    <label class="vs-label">Current Password</label>
                                    <input id="amCurrentPw" name="current_password" type="password" class="vs-input" autocomplete="current-password">
                                </div>
                                <div>
                                    <label class="vs-label">New Password</label>
                                    <input id="amNewPw" name="new_password" type="password" class="vs-input" autocomplete="new-password">
                                </div>
                                <div>
                                    <label class="vs-label">Confirm Password</label>
                                    <input id="amConfirmPw" name="confirm_password" type="password" class="vs-input" autocomplete="new-password">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="vs-modal-footer">
                        <button type="button" class="vs-btn vs-btn-outline" id="accountModalCancel">Cancel</button>
                        <button type="button" class="vs-btn vs-btn-primary" id="accountModalSave">Save Account</button>
                    </div>
                </div>
            </div>`;
        }
    };

    /* ── rendered registry ───────────────────────── */

    var _rendered = {};

    function render(names) {
        if (!Array.isArray(names)) names = [names];
        names.forEach(function (name) {
            if (_rendered[name]) return;
            var fn = _tpl[name];
            if (!fn) { console.warn('ModalInstance: unknown modal "' + name + '"'); return; }
            document.body.insertAdjacentHTML('beforeend', fn());
            _rendered[name] = true;
        });
    }

    /* ── option population helpers ───────────────── */

    function _urlParam(key) {
        return new URLSearchParams(window.location.search).get(key) || '';
    }

    function _buildOpts(selectId, items, getVal, getTxt, selectedVal) {
        var sel = document.getElementById(selectId);
        if (!sel) return;
        var blank = sel.options[0];
        sel.innerHTML = '';
        if (blank) sel.appendChild(blank);
        (items || []).forEach(function (item) {
            var v = getVal(item), t = getTxt(item);
            var opt = document.createElement('option');
            opt.value = v; opt.textContent = t;
            if (v && v === selectedVal) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    function _schoolVal(s) { return typeof s === 'object' ? (s.school_id ? String(s.school_id) : s.school_name) : s; }
    function _schoolTxt(s) { return typeof s === 'object' ? (s.school_name || '') : s; }
    function _schoolName(s) { return typeof s === 'object' ? (s.school_name || '') : s; }

    function populate(name, data) {
        data = data || {};
        var fo = data.filterOptions || {};

        switch (name) {

            case 'filterModal':
                _buildOpts('filterSchoolYear', fo.school_years, function(s){return s;}, function(s){return s;}, _urlParam('school_year'));
                _buildOpts('filterJuniorHs',  fo.junior_high_schools, _schoolVal, _schoolTxt, _urlParam('junior_hs'));
                _buildOpts('filterPreferredHs', fo.senior_high_schools, _schoolVal, _schoolTxt, _urlParam('preferred_hs'));
                break;

            case 'voucherModal':
                var datalist = document.getElementById('vmSchoolYear-list');
                if (datalist) {
                    datalist.innerHTML = '';
                    (fo.school_years || []).forEach(function(sy) {
                        var opt = document.createElement('option');
                        opt.value = sy;
                        datalist.appendChild(opt);
                    });
                }
                _buildOpts('vmJuniorHs',   fo.junior_high_schools, _schoolVal, _schoolTxt, null);
                _buildOpts('vmPreferredHs', fo.senior_high_schools, _schoolVal, _schoolTxt, null);
                break;

            case 'archiveFilterModal':
                _buildOpts('afSchoolYear', data.schoolYears, function(s){return s;}, function(s){return s;}, _urlParam('school_year'));
                _buildOpts('afJuniorHs',   data.juniorHighSchools, _schoolName, _schoolName, _urlParam('junior_hs'));
                _buildOpts('afPreferredHs', data.seniorHighSchools, _schoolName, _schoolName, _urlParam('preferred_hs'));
                break;

            case 'auditFilterModal':
                _buildOpts('auditFilterAction', data.actionOptions,
                    function(s){ return typeof s === 'object' ? (s.action || '') : s; },
                    function(s){ return typeof s === 'object' ? (s.action || '') : s; },
                    _urlParam('action')
                );
                var dtFrom = document.getElementById('auditFilterDateFrom');
                var dtTo   = document.getElementById('auditFilterDateTo');
                if (dtFrom) dtFrom.value = _urlParam('date_from');
                if (dtTo)   dtTo.value   = _urlParam('date_to');
                break;
        }
    }

    /* ── set static filter inputs from URL ───────── */

    function _syncFilterInputs() {
        var inputMap = {
            filterDateFrom: 'date_from',  filterDateTo: 'date_to',
            filterGwaMin:   'gwa_min',    filterGwaMax: 'gwa_max',
            afDateFrom:     'date_from',  afDateTo:     'date_to',
            afGwaMin:       'gwa_min',    afGwaMax:     'gwa_max',
        };
        Object.keys(inputMap).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.value = _urlParam(inputMap[id]);
        });

        var selectMap = {
            filterGender:        'gender',
            filterRemarks:       'remarks',
            filterVoucherStatus: 'voucher_status',
            filterEligibility:   'eligibility',
            afGender:            'gender',
            afRemarks:           'remarks',
            afVoucherStatus:     'voucher_status',
        };
        Object.keys(selectMap).forEach(function (id) {
            var el = document.getElementById(id);
            var val = _urlParam(selectMap[id]);
            if (!el || !val) return;
            for (var i = 0; i < el.options.length; i++) {
                if (el.options[i].value === val) { el.options[i].selected = true; break; }
            }
        });
    }

    /* ── account modal logic ─────────────────────── */

    function _initAccountModal() {
        var overlay   = document.getElementById('accountModal');
        var closeBtn  = document.getElementById('accountModalClose');
        var cancelBtn = document.getElementById('accountModalCancel');
        var saveBtn   = document.getElementById('accountModalSave');
        var openBtn   = document.getElementById('btnOpenAccountModal');
        var msgBox    = document.getElementById('accountModalMsg');
        var csrfInput = document.getElementById('amCsrf');
        if (!overlay || !openBtn) return;

        var dataUrl   = _u('profileData');
        var updateUrl = _u('profileUpdate');

        function _open() {
            overlay.style.display = 'flex';
            msgBox.style.display = 'none';
            msgBox.className = 'mb-3';
            document.getElementById('amCurrentPw').value = '';
            document.getElementById('amNewPw').value = '';
            document.getElementById('amConfirmPw').value = '';
            fetch(dataUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    document.getElementById('amUsername').value   = d.username    || '';
                    document.getElementById('amEmail').value      = d.email       || '';
                    document.getElementById('amRole').textContent = d.role        || '';
                    document.getElementById('amFirstName').value  = d.first_name  || '';
                    document.getElementById('amMiddleName').value = d.middle_name || '';
                    document.getElementById('amLastName').value   = d.last_name   || '';
                });
        }

        function _close() { overlay.style.display = 'none'; }

        openBtn.addEventListener('click', _open);
        closeBtn.addEventListener('click', _close);
        cancelBtn.addEventListener('click', _close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) _close(); });

        saveBtn.addEventListener('click', function () {
            if (typeof refreshCsrfToken === 'function') refreshCsrfToken();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
            fetch(updateUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(document.getElementById('accountModalForm')),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    msgBox.style.display = '';
                    if (data.csrf_value) csrfInput.value = data.csrf_value;
                    if (data.success) {
                        msgBox.className = 'mb-3 vs-alert vs-alert-success';
                        msgBox.textContent = data.message;
                        if (data.full_name) {
                            openBtn.textContent = (data.full_name.trim().charAt(0) || 'U').toUpperCase();
                            openBtn.title = data.full_name;
                        }
                    } else {
                        msgBox.className = 'mb-3 vs-alert vs-alert-error';
                        var msg = data.message || 'An error occurred.';
                        if (data.errors) msg += ' ' + Object.values(data.errors).join(' ');
                        msgBox.textContent = msg;
                    }
                })
                .catch(function () {
                    msgBox.style.display = '';
                    msgBox.className = 'mb-3 vs-alert vs-alert-error';
                    msgBox.textContent = 'Network error. Please try again.';
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Account';
                });
        });
    }

    /* ── auto-initialise ─────────────────────────── */

    function _autoInit() {
        // Render modals declared by views via data-vs-modals attributes
        document.querySelectorAll('[data-vs-modals]').forEach(function (el) {
            var names = el.getAttribute('data-vs-modals').split(',')
                .map(function (n) { return n.trim(); })
                .filter(Boolean);
            render(names);
        });

        // Populate dynamic selects using page-specific data
        var pd = (_VS.pageData || {});

        if (pd.filterOptions) {
            if (_rendered.filterModal)  populate('filterModal',  pd);
            if (_rendered.voucherModal) populate('voucherModal', pd);
        }
        if (pd.schoolYears !== undefined || pd.juniorHighSchools !== undefined) {
            if (_rendered.archiveFilterModal) populate('archiveFilterModal', pd);
        }
        if (pd.actionOptions) {
            if (_rendered.auditFilterModal) populate('auditFilterModal', pd);
        }

        _syncFilterInputs();

        if (_rendered.accountModal) _initAccountModal();
        document.dispatchEvent(new CustomEvent('vs:modals:ready'));
    }

    document.addEventListener('DOMContentLoaded', _autoInit);

    return { render: render, populate: populate };

}());
