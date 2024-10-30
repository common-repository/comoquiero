jQuery(document).ready(function($) {
    $('#qcart-cache-button').on('click', function() {
        var confirmCacheClear = confirm('estás seguro de limpiar la caché AMP?'); 
        if (confirmCacheClear) {
            $.ajax({
                url: qcart_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'qcart_handle_cache',
                },
                success: function(response) {
                    alert("Caché AMP Borrada correctamente!");
                },
                error: function(error) {
                    alert('Error, reintentar.');
                    console.error(error);
                }
            });
        }
    });
});