jQuery(function($){
    // Add new repeater row from template
    $('#coa-history-add').on('click', function(e){
        e.preventDefault();
        var container = $('#coa-history-rows');
        var tpl = $('#coa-history-row-template').html();
        var idx = container.children('.coa-history-row').length;
        tpl = tpl.replace(/__INDEX__/g, idx);
        container.append(tpl);
    });

    // Remove row
    $(document).on('click', '.coa-history-remove', function(e){
        e.preventDefault();
        $(this).closest('.coa-history-row').remove();
        // optionally reindex names - not strictly required for arrays
    });

    // Media picker for COA PDF in COA metabox
    var coaFileFrame;
    $(document).on('click', '#coa_pdf_select', function(e){
        e.preventDefault();
        if (coaFileFrame) { coaFileFrame.open(); return; }
        coaFileFrame = wp.media({
            title: 'Select COA PDF',
            button: { text: 'Use this file' },
            library: { type: 'application/pdf' },
            multiple: false
        });
        coaFileFrame.on('select', function(){
            var attachment = coaFileFrame.state().get('selection').first().toJSON();
            $('#coa_pdf_id').val(attachment.id);
            $('#coa_pdf_preview').html('<a href="'+attachment.url+'" target="_blank">View PDF</a>');
            $('#coa_pdf_remove').show();
        });
        coaFileFrame.open();
    });

    $(document).on('click', '#coa_pdf_remove', function(e){
        e.preventDefault();
        $('#coa_pdf_id').val('');
        $('#coa_pdf_preview').html('');
        $(this).hide();
    });

    // Keep existing functionality - nothing else changed
});
