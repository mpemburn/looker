$(document).ready(function ($) {
    class Searcher {
        constructor() {
            this.placeholders = {
                posts_by_blog_id: 'Enter Blog ID',
                blog_id: 'Enter Blog ID',
                postmeta_keys: 'Enter Meta Key',
                option_name: 'Enter Option Name',
            };
            this.hideSearchTypes = ['list_all', 'post_type', 'plugins', 'themes', 'roles', 'updated'];
            this.showDropdownTypes = ['themes', 'plugins', 'post_type', 'roles'];
            this.urlParams = new URLSearchParams(window.location.search);
            this.dashLinks = $('li[data-value]');
            this.source = $('input:radio[name="source"]');
            this.type = $('#type');
            this.search = $('#search');
            this.dropdown = $('#dropdown');
            this.searchSection = $('#search_section');
            this.dropdownSection = $('#dropdown_section');
            this.excel = $('#save_excel');
            this.found = $('#found');
            this.results = $('#results');
            this.loading = $('#loading');
            this.searchButton = $('#search_btn');
            this.excelButton = $('#excel_btn');
            this.search = $('[name="text"]');
            this.database = $('#database');
            this.excelName = $('[name="excel"]');
            this.search.focus();
            this.currentDatabase;
            this.currentType;
            this.currentSearch;

            this.dropdownSection.hide();

            this.setRadio();
            this.addListeners();
            this.gotoType();
        }

        gotoType() {
            let type = this.urlParams.get('type');
            if (type) {
                this.type.val(type).trigger('change');
            }
        }

        populateDropdown(type) {
            let self = this;

            this.loading.removeClass('hidden');
            this.dropdown.empty();

            $.ajax({
                type: "GET",
                dataType: 'json',
                url: '/get_list?type=' + type + '&database=' + this.database.val(),
                success: function (data) {
                    let result = data[data.type];
                    let label = data.type.charAt(0).toUpperCase() + data.type.slice(1);
                    self.dropdown.append($("<option />").text('Select from ' + label));
                    $.each(result, function (key, text) {
                        let value = typeof key === 'number' ? text : key;
                        self.dropdown.append($("<option />").val(value).text(text));
                    });

                    self.loading.addClass('hidden');
                },
                error: function (msg) {
                    console.log(msg);
                }
            });
        }

        setRadio() {
            let self = this;
            if (this.source.is('*')) {
                let source = this.urlParams.get('source');
                self.source.filter('[value="' + source + '"]').prop('checked', true);
            }
        }

        base64ToBlob(base64, mimetype, slicesize) {
            if (!window.atob || !window.Uint8Array) {
                // The current browser doesn't have the atob function. Cannot continue
                return null;
            }
            mimetype = mimetype || '';
            slicesize = slicesize || 512;
            let bytechars = atob(base64);
            let bytearrays = [];
            for (let offset = 0; offset < bytechars.length; offset += slicesize) {
                let slice = bytechars.slice(offset, offset + slicesize);
                let bytenums = new Array(slice.length);
                for (let i = 0; i < slice.length; i++) {
                    bytenums[i] = slice.charCodeAt(i);
                }
                let bytearray = new Uint8Array(bytenums);
                bytearrays[bytearrays.length] = bytearray;
            }
            return new Blob(bytearrays, {type: mimetype});
        }

        addListeners() {
            let self = this;

            this.dashLinks.on('click', function () {
                let value = $(this).data('value');
                document.location = './search?type=' + value;
            });

            this.source.on('click', function () {
                let baseUrl = document.location.href.replace(document.location.search, '');
                document.location = baseUrl + '?source=' + $(this).val();
            });

            this.type.on('change', function (evt) {
                let type = $(this).val();
                self.currentType = type;
                let hideSearchInput = self.hideSearchTypes.includes(type);
                let showDropdown = self.showDropdownTypes.includes(type);
                let autoClick = ['list_all', 'updated'].includes(type);
                let hiddenValue = hideSearchInput ? 'hidden' : '';
                let placeholder = self.placeholders[type] ? self.placeholders[type] : 'Enter search term';
                self.found.html('');
                self.results.html('');
                self.search.val(hiddenValue);
                self.search.attr('placeholder', placeholder);
                self.searchSection.toggle(!hideSearchInput);
                self.dropdownSection.toggle(showDropdown);
                self.searchButton.prop('disabled', !hideSearchInput);
                self.search.focus();
                self.excel.hide();
                if (showDropdown) {
                    self.populateDropdown(type);
                }
                if (autoClick) {
                    self.searchButton.trigger('click');
                }
            });

            this.database.on('change', function (evt) {
                let type = self.type.val();
                let showDropdown = ($.inArray(type, ['themes', 'plugins']) !== -1);
                self.currentDatabase = $(this).val();
                if (showDropdown) {
                    self.populateDropdown(type);
                }
            });

            this.dropdown.on('change', function (evt) {
                self.search.val($(this).val());
            });

            this.searchButton.on('click', function (evt) {
                evt.preventDefault();
                let formData = $('#search_form').serialize();

                self.loading.removeClass('hidden');
                self.found.html('');
                self.results.html('');
                self.excel.hide();
                self.currentDatabase = self.database.val();
                self.currentType = self.type.val();
                self.currentSearch = self.search.val();

                self.ajaxSetup()
                $.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: "/do_search",
                    data: formData,
                    processData: false,
                    success: function (data) {
                        self.loading.addClass('hidden');
                        self.found.html('<strong>Total Found: ' + data.found + '</strong>');
                        if (data.found > 0) {
                            self.results.html(data.html);
                            new DataTable('#results_table');
                            self.excelName.val(data.filename);
                        }
                        if (!['updated'].includes(self.currentType)) {
                            self.excel.show();
                        }
                        console.log(data);
                    },
                    error: function (msg) {
                        self.loading.addClass('hidden');
                        console.log(msg);
                    }
                });
            });

            this.search.on('keyup', function (evt) {
                let hasText = $(this).val() !== '';
                self.searchButton.prop('disabled', !hasText);
            });

            this.excelButton.on('click', function (evt) {
                self.ajaxSetup()
                $.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: "/download_excel",
                    data: $.param({
                        database: self.currentDatabase,
                        type: self.currentType,
                        search: self.currentSearch,
                        filename: self.excelName.val()
                    }),
                    processData: false,
                    success: function (response) {
                        console.log(response);
                        if (response.error) {
                            alert('ERROR: Unable to create Excel file: \n' + response.error);
                            return;
                        }
                        let a = document.createElement('a');
                        if (window.URL && window.Blob && ('download' in a) && window.atob) {
                            // Do it the HTML5 compliant way
                            let blob = self.base64ToBlob(response.data, response.mime_type);
                            let url = window.URL.createObjectURL(blob);
                            a.href = url;
                            a.download = response.filename;
                            a.click();
                            window.URL.revokeObjectURL(url);
                        }
                    },
                    error: function (msg) {
                        console.log(msg);
                    }
                });
            });
        }

        ajaxSetup() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        }
    }

    new Searcher();
});
