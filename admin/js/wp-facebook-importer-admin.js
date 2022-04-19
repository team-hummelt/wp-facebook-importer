document.addEventListener("DOMContentLoaded", function () {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const lang = fb_import_ajax_obj.alert_msg;



    /**================================================
     ================ ADMIN XHR FORMULAR ==============
     ==================================================
     */
    function api_xhr_experience_reports_form_data(data, is_formular = true, callback) {
        let xhr = new XMLHttpRequest();
        let formData = new FormData();

        if (is_formular) {
            let input = new FormData(data);
            for (let [name, value] of input) {
                formData.append(name, value);
            }
        } else {
            for (let [name, value] of Object.entries(data)) {
                formData.append(name, value);
            }
        }

        formData.append('_ajax_nonce', fb_import_ajax_obj.nonce);
        formData.append('action', 'FBImporterHandle');
        xhr.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                if (typeof callback === 'function') {
                    xhr.addEventListener("load", callback);
                    return false;
                }
            }
        }
        xhr.open('POST', fb_import_ajax_obj.ajax_url, true);
        xhr.send(formData);
    }

    /** ================================================================
     * ======================= Formular Autosave =======================
     * =================================================================*/
    let appSendFormTimeout;
    let appSendFormular = document.querySelectorAll(".sendAdminAjaxForm:not([type='button'])");
    if (appSendFormular) {
        let formNodes = Array.prototype.slice.call(appSendFormular, 0);
        formNodes.forEach(function (formNodes) {
            formNodes.addEventListener("keyup", form_input_ajax_handle, {passive: true});
            formNodes.addEventListener('touchstart', form_input_ajax_handle, {passive: true});
            // formNodes.addEventListener('click', form_input_ajax_handle, {passive: true});
            formNodes.addEventListener('change', form_input_ajax_handle, {passive: true});

            function form_input_ajax_handle() {
                let spin = formNodes.querySelector('.ajax-status-spinner');
                spin.innerHTML = '';
                spin.classList.add('wait');
                clearTimeout(appSendFormTimeout);
                appSendFormTimeout = setTimeout(function () {
                    api_xhr_experience_reports_form_data(formNodes, true, formular_auto_save_callback);
                    return false;
                }, 1000);
            }
        });
    }

    function formular_auto_save_callback() {
        let data = JSON.parse(this.responseText);
        if (data.type == 'set_plugin_settings') {

        }
        show_ajax_spinner(data);
    }


    /**====================================
     ========== FACEBOOK SEITEN ===========
     ======================================
     */
    if (urlParams.get('page') === 'fb-importer-sites') {
        getImportDataTable();
        getCronJobDataTable();
    }

    if (urlParams.get('page') === 'wp-facebook-importer-settings') {
        let formData = {
            'method':'get_next_sync_time'
        }
        api_xhr_experience_reports_form_data(formData, false, set_next_sync_timestamp_callback);

    }

    function set_next_sync_timestamp_callback() {
        let data = JSON.parse(this.responseText);
        if(data.status){
            let endtime = new Date(data.next_time);
            initializeClock('#nextSyncTime', endtime);
        }
    }

    jQuery(document).on('click', '.btn-collapse-toggle', function () {
        let btnCol = jQuery('.btn-collapse-toggle');
        btnCol.prop('disabled', false).removeClass('active');
        jQuery(this).prop('disabled', true).addClass('active');
        ImportTableDetails.draw('page');
        if(jQuery(this).attr('data-load') == 'log-table'){
        cronJobTable.draw('page');
            let formData = {
                'method':'get_next_sync_time'
            }
            api_xhr_experience_reports_form_data(formData, false, set_next_sync_timestamp_callback);

        }
    });

    jQuery(document).on('submit', '.admin-ajax-formular', function (e) {
        let formData = jQuery(this).closest("form").get(0);
        api_xhr_experience_reports_form_data(formData, true, formular_admin_settings_data_callback);
        e.preventDefault();
    });

    jQuery(document).on('dblclick', '.show-cronjob-settings', function () {
        jQuery('.cronjob-settings').toggleClass('d-none');
    });

    jQuery(document).on('click', '.btn-delete-log', function () {
        let formData = {
            'method': 'delete_log',
            'type':jQuery(this).attr('data-type'),
            'id':jQuery(this).attr('data-id')
        }
        api_xhr_experience_reports_form_data(formData, false, delete_log_data_callback);
    });

      function delete_log_data_callback() {
          let data = JSON.parse(this.responseText);
          cronJobTable.draw('page');
      }

    function formular_admin_settings_data_callback() {
        let data = JSON.parse(this.responseText);
        switch (data.type) {
            case'insert':
                jQuery('.admin-ajax-formular').trigger('reset');
                break;
            case'cronjob_system_settings':
                jQuery('.cronjob-settings').addClass('d-none');
                break;
        }
        swal_alert(data);
    }

    jQuery(document).on('click', '.btn-load-import', function () {
        let formData = {
            'method': 'get_import_data',
            'id': jQuery(this).attr('data-id')
        }
        api_xhr_experience_reports_form_data(formData, false, load_import_data_callback);
    });

    function load_import_data_callback() {
        let data = JSON.parse(this.responseText);

        if (data.status) {
            let collEdit = document.getElementById('collapseEditFBSite');
            collEdit.innerHTML = data.template;
            new bootstrap.Collapse(collEdit, {
                toggle: true,
                parent: '#displayDataParent'
            });
            load_bs_tooltip();
        } else {
            swal_alert(data);
        }
    }

    jQuery(document).on('click', '.btn-form-action', function () {
        let type = jQuery(this).attr('data-type');
        let editCollapse = document.getElementById('collapseEditFBSite');
        let tableCollapse = document.getElementById('collapseOverviewFBSite');
        let formData;
        let wait =  jQuery('.ajax-sync-wait');
        switch (type) {
            case 'show-import-table':
                editCollapse.innerHTML = '';
                ImportTableDetails.draw('page');
                new bootstrap.Collapse(tableCollapse, {
                    toggle: true,
                    parent: '#displayDataParent'
                });
                break;
            case 'import':
                formData = {
                    'method': 'import_delete_handle',
                    'type': type,
                    'id': jQuery(this).attr('data-id'),
                    'html': `<span class="text-white-50">${lang.delete_import_note}</span>`,
                    'title': lang.del_import,
                    'btnText': lang.btn_delete_import,
                    'confirm_dialog': true,

                }
                swal_fire_delete_modal(formData);
                break;
            case'posts':
                formData = {
                    'method': 'import_delete_handle',
                    'type': type,
                    'id': jQuery(this).attr('data-id'),
                    'html': `<span class="text-white-50">${lang.delete_posts_note}</span>`,
                    'title': lang.del_posts,
                    'btnText': lang.btn_delete_posts,
                    'confirm_dialog': true,

                }
                swal_fire_delete_modal(formData);
                break;
            case'events':
                formData = {
                    'method': 'import_delete_handle',
                    'type': type,
                    'id': jQuery(this).attr('data-id'),
                    'html': `<span class="text-white-50">${lang.alert_delete_all_events_msg}</span>`,
                    'title': lang.alert_delete_all_events,
                    'btnText': lang.alle_events_delete,
                    'confirm_dialog': true,

                }
                swal_fire_delete_modal(formData);
                break;
            case 'reset_sync_date':
                formData = {
                    'method': 'import_delete_handle',
                    'type': type,
                    'id': jQuery(this).attr('data-id'),
                }
                api_xhr_experience_reports_form_data(formData, false, change_import_callback)
                break;
            case'sync_fb_posts':
                wait.removeClass('d-none');
                formData = {
                    'method':type,
                    'id': jQuery(this).attr('data-id'),
                }
                api_xhr_experience_reports_form_data(formData, false, sync_post_events_callback)
                break;
            case'sync_fb_events':
                wait.removeClass('d-none');
                formData = {
                    'method':type,
                    'id': jQuery(this).attr('data-id'),
                }
                api_xhr_experience_reports_form_data(formData, false, sync_post_events_callback)
                break;
            case'log-aktualisieren':
                cronJobTable.draw('page');
                break;
        }
    });

    function sync_post_events_callback() {
        let data = JSON.parse(this.responseText);
        jQuery('.ajax-sync-wait').addClass('d-none');
        jQuery('.syn_date_wrapper').addClass('d-none');
        jQuery('.count-imports').addClass('d-none');
        if(data.status){
            swal_alert(data);
        } else {
            let alType;
            data.status_type == 'warning' ? alType = 'info' : alType = 'danger';
            alert(data.msg, alType);
        }
    }

    jQuery(document).on('click', '#TableImports .form-check-input', function () {
        let formData = {
            'method': 'change_import_settings',
            'id': jQuery(this).attr('data-id'),
            'type': jQuery(this).attr('data-type')
        }
        api_xhr_experience_reports_form_data(formData, false, change_import_callback);
    });

    function change_import_callback() {
        let data = JSON.parse(this.responseText);
        if(data.status) {
            success_message(data.msg);
        } else {
            swal_alert(data);
        }

    }

    jQuery(document).on('submit', '.admin-kategorie-formular', function (e) {
        let formData = jQuery(this).closest("form").get(0);
        api_xhr_experience_reports_form_data(formData, true, formular_admin_kategorie_data_callback);
        e.preventDefault();
    });

    function formular_admin_kategorie_data_callback() {
        let data = JSON.parse(this.responseText);
        if (data.status) {
            let FormModalEl = document.getElementById('addCategoryModal');
            let modalForm = document.getElementById("modalFormular");
            let modal = bootstrap.Modal.getInstance(FormModalEl);
            modal.hide();
            modalForm.reset();
            let sel = '';
            let select = `<option value="">${data.selLang} ...</option>`;
            jQuery.each(data.select, function (key, val) {
                if (data.catName === val.name) {
                    sel = 'selected';
                } else {
                    sel = '';
                }
                select += `<option value="${val.term_id}" ${sel}>${val.name}</option>`;
                jQuery("[name='post_cat']").html(select);
                jQuery("[name='event_cat']").html(select);
                swal_alert(data);
            });
        } else {
            alert(data.msg, 'danger');
        }
    }

    jQuery(document).on('click', '.change-disabled', function () {
        let target = jQuery(this).attr('data-target');
        let syncTime = jQuery('.next-sync-time');
        if (jQuery(this).prop('checked')) {
            jQuery(target).prop('disabled', false);
            syncTime.removeClass('d-none');
            let formData = {
                'method':'get_next_sync_time'
            }
            api_xhr_experience_reports_form_data(formData, false, set_next_sync_timestamp_callback);
        } else {
            jQuery(target).prop('disabled', true);
            syncTime.addClass('d-none');
        }
    });


    jQuery(document).on('click', '.show-access-token', function () {
        let inputToken = jQuery('#inputToken');
        if (jQuery(this).hasClass('show')) {
            inputToken.val('');
            jQuery(this).removeClass('show');
        } else {
            jQuery(this).addClass('show');
            let formData = {
                'method': 'get_access_token'
            }
            api_xhr_experience_reports_form_data(formData, false, show_access_token_callback);
        }
        jQuery('.show-btn-text').toggleClass('d-none');
    });

    function show_access_token_callback() {
        let data = JSON.parse(this.responseText);
        let inputToken = jQuery('#inputToken');
        inputToken.val(data.msg);
    }

    let NotificationModal = document.getElementById('AjaxResponseModal');
    if (NotificationModal) {
        let modalContent = document.querySelector('#AjaxResponseModal .modal-content');
        modalContent.innerHTML = '';
        NotificationModal.addEventListener('show.bs.modal', function () {
            let formData = {
                'method': 'check_status_access_token'
            }
            api_xhr_experience_reports_form_data(formData, false, check_access_token_callback);
        });

        function check_access_token_callback() {
            let data = JSON.parse(this.responseText);
            if (data.status) {
                let modalContent = document.querySelector('#AjaxResponseModal .modal-content');
                modalContent.innerHTML = data.template;
            }
        }
    }

    function delete_import_callback() {
        let data = JSON.parse(this.responseText);
        switch (data.type) {
            case'import':
                if(data.status){
                    let editCollapse = document.getElementById('collapseEditFBSite');
                    let tableCollapse = document.getElementById('collapseOverviewFBSite');
                    editCollapse.innerHTML='';
                    ImportTableDetails.draw('page');
                    new bootstrap.Collapse(tableCollapse, {
                        toggle: true,
                        parent: '#displayDataParent'
                    });
                }
                swal_alert(data);
                break;
            case'posts':
            case'event':
                jQuery('.syn_date_wrapper').addClass('d-none');
                if(data.status){
                    success_message(data.msg);

                } else {
                    swal_alert(data);
                }
                break;
        }
    }


    /**======================================
     ========== AJAX SPINNER SHOW  ===========
     =========================================
     */
    function show_ajax_spinner(data) {
        let msg = '';
        if (data.status) {
            msg = '<i class="text-success fw-bold bi bi-check2-circle"></i>&nbsp; Saved! Last: ' + data.msg;
        } else {
            msg = '<i class="text-danger bi bi-exclamation-triangle"></i>&nbsp; ' + data.msg;
        }
        let spinner = document.querySelector('.' + data.type);
        spinner.classList.remove('wait');
        spinner.innerHTML = msg;
    }

    function swal_alert(data) {
        if (data.status) {
            Swal.fire({
                position: 'top-end',
                title: data.title,
                text: data.msg,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                showClass: {
                   // popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-success-container'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        } else {
            Swal.fire({
                position: 'top-end',
                title: data.title,
                text: data.msg,
                icon: 'error',
                timer: 2000,
                showConfirmButton: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-error-container'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        }
    }

    function swal_fire_delete_modal(data) {
        Swal.fire({
            title: data.title,
            reverseButtons: true,
            html: data.html,
            confirmButtonText: data.btnText,
            cancelButtonText: lang.Abbrechen,
            showClass: {
                //popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'swal-delete-container'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                api_xhr_experience_reports_form_data(data, false, delete_import_callback);
            }
        });
    }

    function alert(message = '', type = '', id = false) {
        if (!message) {
            return false;
        }
        let alertContainer = document.querySelector('.alert-wrapper');
        if (alertContainer) {
            alertContainer.remove();
        }
        let wrapper = document.createElement('div');
        wrapper.classList.add('alert-wrapper');
        let container = '';
        id ? container = '.liveAlertPlaceholder' + id : container = '.liveAlertPlaceholder';

        let alertPlaceholder = document.querySelector(container);
        wrapper.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible" role="alert"><i class="bi bi-exclamation-triangle"></i>&nbsp; ' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
        alertPlaceholder.append(wrapper);
    }

    function load_bs_tooltip(){
        let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        let tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    }

    /** ============================================================
     * ======================= COUNTDOWN UHR =======================
     * =============================================================*/
    function getTimeRemaining(endtime) {
        const total = Date.parse(endtime) - Date.parse(new Date());
        const seconds = Math.floor((total / 1000) % 60);
        const minutes = Math.floor((total / 1000 / 60) % 60);
        const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
        const days = Math.floor(total / (1000 * 60 * 60 * 24));

        return {
            total,
            days,
            hours,
            minutes,
            seconds
        };
    }

    function initializeClock(target, endtime) {

        if(!target){
            return false;
        }
        const timeinterval = setInterval(() => {
            const t = getTimeRemaining(endtime);
            const clock = document.querySelector(target);
            clock.innerHTML = `<small><span class="lh-1 font-strong">${t.days > 0 ? t.days + ' Day(s) '  : ''} ${('0' + t.hours).slice(-2)}:${('0' + t.minutes).slice(-2)}:${('0' + t.seconds).slice(-2)}</span></small>`;
            if (t.total <= 0) {
                clearInterval(timeinterval);
                setInterval(() => {
                    let formData = {
                        'method':'get_next_sync_time'
                    }
                    api_xhr_experience_reports_form_data(formData, false, set_next_sync_timestamp_callback);
                },1500);
            }
        }, 1000);
    }

    function success_message(msg) {
        let x = document.getElementById("snackbar-success");
        x.innerHTML = msg;
        x.className = "show";
        setTimeout(function () {
            x.className = x.className.replace("show", "");
        }, 3000);
    }
});
