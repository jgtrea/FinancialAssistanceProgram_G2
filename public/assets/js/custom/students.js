(function ($) {
  function getCsrfData() {
    var csrfName = $('meta[name="csrf-token-name"]').attr("content");
    var csrfValue = $('meta[name="csrf-token-value"]').attr("content");
    var postData = {};

    if (csrfName && csrfValue) {
      postData[csrfName] = csrfValue;
    }

    return postData;
  }

  $(document).on("click", ".deleteBtn", function () {
    var deleteUrl = $(this).data("delete-url");

    if (!deleteUrl || !confirm("Archive this student?")) {
      return;
    }

    $.ajax({
      url: deleteUrl,
      type: "POST",
      data: getCsrfData(),
      dataType: "json",
      success: function (response) {
        showToast(response.message, "error");

        if (response.status === "success") {
          location.reload();
        }
      },
      error: function (xhr) {
        console.log(xhr.responseText);
        showToast('Failed to archive student. Check console.', 'error');
      },
    });
  });

  $(document).on("submit", "#studentForm", function (event) {
    event.preventDefault();

    var $form = $(this);

    $.ajax({
      url: $form.attr("action"),
      type: $form.attr("method") || "POST",
      data: $form.serialize(),
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          try { sessionStorage.setItem("__pendingToast", JSON.stringify({ msg: response.message, type: "success" })); } catch (e) {}
          window.location.href = $form.data("redirect-url");
        } else {
          showToast(response.message, "error");
        }
      },
      error: function (xhr) {
        console.log(xhr.responseText);
        showToast('Something went wrong. Check console.', 'error');
      },
    });
  });
})(jQuery);
