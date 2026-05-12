(function ($) {
    function getCsrfData() {
        var csrfName = $('meta[name="csrf-token-name"]').attr('content');
        var csrfValue = $('meta[name="csrf-token-value"]').attr('content');
        var postData = {};

        if (csrfName && csrfValue) {
            postData[csrfName] = csrfValue;
        }

        return postData;
    }

    // Save User (Create / Update)
    $(document).on('submit', '#userForm', function (event) {
        event.preventDefault();

        var $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method') || 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                alert(response.message);

                if (response.status === 'success') {
                    window.location.href = $form.data('redirect-url');
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                $('#alertBox').html('<div class="alert alert-danger">Something went wrong. Check console.</div>');
            }
        });
    });

    // Archive User
    $(document).on('click', '.archiveUserBtn', function () {
        var archiveUrl = $(this).data('archive-url');

        if (!archiveUrl || !confirm('Archive this user?')) {
            return;
        }

        $.ajax({
            url: archiveUrl,
            type: 'POST',
            data: getCsrfData(),
            dataType: 'json',
            success: function (response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Failed to archive user. Check console.');
            }
        });
    });

    // Restore User
    $(document).on('click', '.restoreUserBtn', function () {
        var restoreUrl = $(this).data('restore-url');

        if (!restoreUrl || !confirm('Restore this user?')) {
            return;
        }

        $.ajax({
            url: restoreUrl,
            type: 'POST',
            data: getCsrfData(),
            dataType: 'json',
            success: function (response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Failed to restore user. Check console.');
            }
        });
    });

})(jQuery);