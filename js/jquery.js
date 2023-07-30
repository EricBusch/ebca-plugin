jQuery(function ($) {

    $(".eb_media_categories").on("change", "input", function (e) {

        let checkboxName = $(this).attr('name');
        let attachmentId = $(this).attr("data-attachment-id");
        let checkedTermIds = [];

        $("input:checkbox[name=" + checkboxName + "]:checked").each(function () {
            checkedTermIds.push($(this).val());
        });

        $.ajax({
            type: "POST",
            url: eb.ajax_url,
            data: {
                action: "eb_update_attachment_media_category_ids",
                eb_security: eb.nonce,
                attachment_id: attachmentId,
                term_ids: checkedTermIds
            }
        }).done(function (html) {
            $('#' + checkboxName + '_result').show().html('saved').fadeOut(350);
        });
    });

}); // jQuery(function($) {