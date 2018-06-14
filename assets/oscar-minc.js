(function($) {
    $(document).ready(function() {
        oscar.init();
        oscar.uploadProcess();
    });

    var oscar = {
        init: function() {
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
        },
        uploadProcess: function () {
            var reader = {};
            var file = {};
            var slice_size = 1000 * 1024;

            function start_upload( event ) {
                event.preventDefault();

                reader = new FileReader();
                file = document.querySelector( '#oscar-video' ).files[0];

                upload_file( 0 );
            }

            // $( '#oscar-video-upload-btn' ).on( 'click', start_upload );
            // $( '#oscar-video-upload-btn' ).on( 'click', videoUpload );

            $(document).on('change', '#oscar-video', function(e) {
                console.log($('#oscar-video')[0].files[0]);
                if ($(this)[0].files[0]) {
                    var errors = validateMovie( $('#oscar-video')[0].files[0] );
                    if( errors.length ){
                        console.log(errors)
                    } else {
                        $('#oscar-video-name').text($(this)[0].files[0].name);
                        $('#oscar-video-upload-btn').removeAttr('disabled');
                        $('#oscar-video-form .video-drag-area').addClass('ready-to-upload');
                    }
                } else {
                    $('#oscar-video-name').text('');
                    $('#oscar-video-upload-btn').attr('disabled', 'disabled');
                    $('#oscar-video-form .video-drag-area').removeClass('ready-to-upload');
                }

                function validateMovie(movieObj) {
                    var errors = [];
                    if( movieObj.size >  $('#movie_max_size').val() ){
                        errors.push('O tamanho do arquivo excede o limite permitido.');
                    }

                    return errors;
                }
            });


            $("#oscar-video-form").on('submit', function(e) {
                e.preventDefault();
                $('#oscar-video-form .myprogress').css('width', '0');
                $('#oscar-video-form .msg').text('');
                // var filename = $('#filename').val();
                var filename = 'Foobar';
                var oscarVideo = $('#oscar-video').val();
                if (oscarVideo == '') {
                    alert('Por favor, selecione um arquivo para upload.');
                    return;
                }
                var formData = new FormData();
                formData.append('nonce', oscar_minc_vars.upload_file_nonce);
                formData.append('oscarVideo', $('#oscar-video')[0].files[0]);
                formData.append('action', 'upload_oscar_video');
                formData.append('post_id', $('#post_id').val());
                // $('#btn').attr('disabled', 'disabled');
                $('#oscar-video-form .msg').text('Upload em progresso, por favor, aguarde...');
                $.ajax({
                    url: oscar_minc_vars.ajaxurl,
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    beforeSend: function () {
                        $('#upload-status').removeClass('hidden');
                    },
                    // this part is progress bar
                    xhr: function () {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function (evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total;
                                percentComplete = parseInt(percentComplete * 100);
                                $('#oscar-video-form .myprogress').text(percentComplete + '%');
                                $('#oscar-video-form .myprogress').css('width', percentComplete + '%');
                                if( percentComplete === 100 ){
                                    $('#oscar-video-form .msg').html('Finalizando o processo de envio do filme.');
                                    $('#oscar-video-form .myprogress').removeClass('progress-bar-animated');
                                }
                            }
                        }, false);
                        return xhr;
                    },
                    success: function (res) {
                        console.log(res);
                        if( res.success ){
                            $('#oscar-video-form .msg').addClass('success');
                            $('#oscar-video-form .msg').html(res.data);
                        } else {
                            $('#oscar-video-form .myprogress').text('0%');
                            $('#oscar-video-form .myprogress').css('width', '0%');
                            $('#oscar-video-form .msg').html(res.data);
                        }
                    },
                    error: function( jqXHR, textStatus, errorThrown ) {
                        console.error( jqXHR, textStatus, errorThrown );
                    }
                });
            });

            function upload_file( start ) {
                var next_slice = start + slice_size + 1;
                var blob = file.slice( start, next_slice );
                var movie = $('#oscar-video')[0].files[0];

                console.log(movie);

                reader.onloadend = function( event ) {
                    if ( event.target.readyState !== FileReader.DONE ) {
                        return;
                    }

                    $.ajax( {
                        url: oscar_minc_vars.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        cache: false,
                        processData: false,
                        contentType: false,
                        data: {
                            action: 'upload_oscar_video',
                            post_id: $('#post_id').val(),
                            file_data: event.target.result,
                            file: file.name,
                            file_type: file.type,
                            movie_data: file[0],
                            nonce: oscar_minc_vars.upload_file_nonce
                        },
                        error: function( jqXHR, textStatus, errorThrown ) {
                            console.log( jqXHR, textStatus, errorThrown );
                        },
                        success: function( data ) {
                            console.log(data);

                            $('#upload-status').removeClass('hidden');

                            var size_done = start + slice_size;
                            var percent_done = Math.floor( ( size_done / file.size ) * 100 );

                            if ( next_slice < file.size ) {
                                // Update upload progress
                                $('#upload-status .progress-bar').css('width', percent_done + '%');
                                $( '#dbi-upload-progress' ).html( 'Uploading File - ' + percent_done + '%' );

                                // More to upload, call function recursively
                                upload_file( next_slice );
                            } else {
                                // Update upload progress
                                $('#upload-status .progress-bar').css('width', '100%');
                                $( '#dbi-upload-progress' ).html( 'Upload Complete!' );
                            }
                        }
                    } );
                };

                reader.readAsDataURL( blob );
            }
        }
    };
})(jQuery);