$(document).ready(function ($) {
    class Searcher {
        constructor() {
            this.source = $('input:radio[name="source"]');
            this.type = $('#type');
            this.search = $('#search');
            this.searchSection = $('#search_section');
            this.found = $('#found');
            this.results = $('#results');
            this.loading = $('#loading');
            this.searchButton = $('#search_btn');
            this.search = $('[name="text"]');
            this.search.focus();

            this.setRadio();
            this.addListeners();
        }

        setRadio() {
            let self = this;
            if (this.source.is('*')) {
                let urlParams = new URLSearchParams(document.location.search);
                let source = urlParams.get('source');
                self.source.filter('[value="' + source + '"]').prop('checked', true);
            }
        }

        addListeners() {
            let self = this;
            this.source.on('click', function () {
                let baseUrl = document.location.href.replace(document.location.search, '');
                document.location = baseUrl + '?source=' + $(this).val();
            });

            this.type.on('change', function (evt) {
                let updatedSelected = ($(this).val() === 'updated')
                let placeholder = updatedSelected ? 'placeholder' : '';
                self.search.val(placeholder);
                self.searchSection.toggle(!updatedSelected);
                self.searchButton.prop('disabled', !updatedSelected);
            });

            this.searchButton.on('click', function (evt) {
                evt.preventDefault();
                let formData = $('#search_form').serialize();

                self.loading.removeClass('hidden');
                self.found.html('');
                self.results.html('');

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
                self.searchButton.prop('disabled', ! hasText);
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
