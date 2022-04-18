let ImportTableDetails
function getImportDataTable() {
    ImportTableDetails = new DataTable('#TableImports', {
        "language": {
            "url": fb_import_ajax_obj.data_table,
        },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Alle"]],
        "paging": true,
        "pageLength": 10,
        "columns": [
            null,
            null,
            null,
            null,
            {
                "width": "19%"
            },
            null,
            null,
            null,
            {
                "width": "6%"
            }
        ],

        columnDefs: [{
            orderable: false,
            targets: [8]
        }, {
            targets: [0, 2, 3, 4, 6, 7],
            className: 'align-middle'
        }, {
            targets: [1 ,5 ,8],
            className: 'align-middle text-center'
        }
        ],

        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": {
            url: fb_import_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                method: 'imports_data_table',
                '_ajax_nonce': fb_import_ajax_obj.nonce,
                'action': 'FBImporterHandle',
            }
        }
    });
}