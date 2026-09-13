document.addEventListener('DOMContentLoaded', function () {

    /* ============================================================
       CHARACTER COUNTER
    ============================================================ */

    const textarea = document.getElementById('content');
    const counter = document.getElementById('charCount');

    if (textarea && counter) {

        textarea.addEventListener('input', function () {

            counter.textContent = this.value.length;

        });

    }


    /* ============================================================
       EXPERIENCE SEARCH
    ============================================================ */

    const experienceSearch =
        document.getElementById('experienceSearch');

    const experienceSelect =
        document.getElementById('experience_id');

    const experienceMessage =
        document.getElementById('experienceSearchMessage');

    const experienceResults =
        document.getElementById('experienceSearchResults');


    if (
        experienceSearch &&
        experienceSelect &&
        experienceResults
    ) {

        /* ========================================================
           SHOW CURRENT EXPERIENCE
        ======================================================== */

        const initiallySelected =
            experienceSelect.options[
                experienceSelect.selectedIndex
            ];


        if (
            initiallySelected &&
            experienceSelect.value
        ) {

            experienceSearch.value =
                initiallySelected.dataset.name || '';

            experienceMessage.textContent =
                'Experience selected.';

        }


        /* ========================================================
           SEARCH
        ======================================================== */

        experienceSearch.addEventListener(
            'input',
            function () {

                const keyword =
                    this.value
                        .trim()
                        .toLowerCase();


                let visibleCount = 0;


                experienceResults.innerHTML = '';


                if (keyword === '') {

                    experienceMessage.textContent = '';

                    experienceResults.classList.remove(
                        'active'
                    );

                    return;

                }


                /* =================================================
                   LOOP THROUGH EXPERIENCES
                ================================================== */

                Array.from(
                    experienceSelect.options
                ).forEach(function (option, index) {

                    if (index === 0) {
                        return;
                    }


                    const searchText =
                        option.dataset.search || '';


                    const matched =
                        searchText.includes(keyword);


                    if (!matched) {
                        return;
                    }


                    visibleCount++;


                    const name =
                        option.dataset.name || '';

                    const location =
                        option.dataset.location || '';

                    const type =
                        option.dataset.type || '';

                    const category =
                        option.dataset.category || '';


                    /* =============================================
                       CREATE RESULT
                    ============================================== */

                    const resultItem =
                        document.createElement('button');


                    resultItem.type = 'button';

                    resultItem.className =
                        'experience-result-item';


                    let metaParts = [];


                    if (location) {

                        metaParts.push(
                            '📍 ' + location
                        );

                    }


                    if (type) {

                        metaParts.push(type);

                    }


                    if (category) {

                        metaParts.push(category);

                    }


                    resultItem.innerHTML = `

                        <div class="experience-result-name">
                            ${escapeHtml(name)}
                        </div>

                        ${
                            metaParts.length > 0
                            ?
                            `
                            <div class="experience-result-meta">
                                ${metaParts.map(escapeHtml).join(' · ')}
                            </div>
                            `
                            :
                            ''
                        }

                    `;


                    /* =============================================
                       SELECT RESULT
                    ============================================== */

                    resultItem.addEventListener(
                        'click',
                        function () {

                            experienceSelect.value =
                                option.value;


                            experienceSearch.value =
                                name;


                            experienceResults.innerHTML =
                                '';

                            experienceResults.classList.remove(
                                'active'
                            );


                            experienceMessage.textContent =
                                'Experience selected.';

                        }
                    );


                    experienceResults.appendChild(
                        resultItem
                    );

                });


                /* =================================================
                   SEARCH MESSAGE
                ================================================== */

                if (visibleCount === 0) {

                    experienceMessage.textContent =
                        'No matching experiences found.';

                    experienceResults.classList.remove(
                        'active'
                    );

                }
                else {

                    experienceMessage.textContent =
                        visibleCount
                        + ' experience'
                        + (
                            visibleCount === 1
                            ? ''
                            : 's'
                        )
                        + ' found.';


                    experienceResults.classList.add(
                        'active'
                    );

                }

            }
        );


        /* ========================================================
           SELECT CHANGE
        ======================================================== */

        experienceSelect.addEventListener(
            'change',
            function () {

                const selectedOption =
                    this.options[
                        this.selectedIndex
                    ];


                if (!this.value) {

                    experienceSearch.value = '';

                    experienceMessage.textContent = '';

                    experienceResults.innerHTML = '';

                    experienceResults.classList.remove(
                        'active'
                    );

                    return;

                }


                const selectedName =
                    selectedOption.dataset.name || '';


                experienceSearch.value =
                    selectedName;


                experienceMessage.textContent =
                    'Experience selected.';


                experienceResults.innerHTML = '';

                experienceResults.classList.remove(
                    'active'
                );

            }
        );

    }


    /* ============================================================
       CURRENT PHOTO REMOVAL
    ============================================================ */

    const currentPhotoButtons =
        document.querySelectorAll(
            '.edit-remove-image'
        );


    currentPhotoButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const photo =
                    this.closest(
                        '.edit-current-photo'
                    );


                if (!photo) {
                    return;
                }


                const checkbox =
                    photo.querySelector(
                        '.keep-image-checkbox'
                    );


                if (!checkbox) {
                    return;
                }


                /* ==============================================
                   REMOVE PHOTO
                =============================================== */

                if (checkbox.checked) {

                    checkbox.checked = false;

                    photo.classList.add(
                        'is-removed'
                    );

                }


                /* ==============================================
                   RESTORE PHOTO
                =============================================== */

                else {

                    checkbox.checked = true;

                    photo.classList.remove(
                        'is-removed'
                    );

                }


                updatePhotoCount();

            }
        );

    });


    /* ============================================================
       NEW IMAGE UPLOAD
    ============================================================ */

    const imageInput =
        document.getElementById('imageInput');

    const preview =
        document.getElementById('imagePreview');

    const photoPreviewPanel =
        document.getElementById('photoPreviewPanel');

    const photoCount =
        document.getElementById('photoCount');


    let selectedFiles = [];


    /* ============================================================
       GET KEPT IMAGE COUNT
    ============================================================ */

    function getKeptImageCount() {

        return document.querySelectorAll(
            '.keep-image-checkbox:checked'
        ).length;

    }


    /* ============================================================
       UPDATE TOTAL PHOTO COUNT
    ============================================================ */

    function updatePhotoCount() {

        const currentCount =
            getKeptImageCount();


        const totalCount =
            currentCount +
            selectedFiles.length;


        if (photoCount) {

            photoCount.textContent =
                `(${totalCount}/10)`;

        }

    }


    /* ============================================================
       IMAGE SELECTION
    ============================================================ */

    if (imageInput) {

        imageInput.addEventListener(
            'change',
            function () {

                const newFiles =
                    Array.from(this.files);


                const currentCount =
                    getKeptImageCount();


                if (
                    currentCount +
                    selectedFiles.length +
                    newFiles.length
                    > 10
                ) {

                    alert(
                        'You can upload a maximum of 10 photos in total.'
                    );


                    this.value = '';

                    return;

                }


                selectedFiles.push(
                    ...newFiles
                );


                renderPreview();

                updateFileInput();

                updatePhotoCount();

            }
        );

    }


    /* ============================================================
       RENDER NEW IMAGE PREVIEW
    ============================================================ */

    function renderPreview() {

        if (!preview) {
            return;
        }


        preview.innerHTML = '';


        if (photoPreviewPanel) {

            if (selectedFiles.length > 0) {

                photoPreviewPanel.classList.add(
                    'has-photos'
                );

            }
            else {

                photoPreviewPanel.classList.remove(
                    'has-photos'
                );

            }

        }


        selectedFiles.forEach(function (file, index) {

            const reader =
                new FileReader();


            reader.onload =
                function (event) {

                    const div =
                        document.createElement(
                            'div'
                        );


                    div.className =
                        'preview-item';


                    div.innerHTML = `

                        <img
                            src="${event.target.result}"
                            alt="Selected photo"
                        >

                        <button
                            type="button"
                            class="remove-image"
                            data-index="${index}"
                            aria-label="Remove photo"
                        >
                            ×
                        </button>

                    `;


                    preview.appendChild(div);

                };


            reader.readAsDataURL(file);

        });

    }


    /* ============================================================
       REMOVE NEW IMAGE
    ============================================================ */

    if (preview) {

        preview.addEventListener(
            'click',
            function (event) {

                if (
                    event.target.classList.contains(
                        'remove-image'
                    )
                ) {

                    const index =
                        parseInt(
                            event.target.dataset.index,
                            10
                        );


                    if (Number.isNaN(index)) {
                        return;
                    }


                    selectedFiles.splice(
                        index,
                        1
                    );


                    renderPreview();

                    updateFileInput();

                    updatePhotoCount();

                }

            }
        );

    }


    /* ============================================================
       UPDATE FILE INPUT
    ============================================================ */

    function updateFileInput() {

        if (!imageInput) {
            return;
        }


        const dataTransfer =
            new DataTransfer();


        selectedFiles.forEach(function (file) {

            dataTransfer.items.add(file);

        });


        imageInput.files =
            dataTransfer.files;

    }


    /* ============================================================
       INITIAL PHOTO COUNT
    ============================================================ */

    updatePhotoCount();


    /* ============================================================
       PREVENT DOUBLE SUBMISSION
    ============================================================ */

    const editPostForm =
        document.getElementById(
            'editPostForm'
        );

    const saveChangesButton =
        document.getElementById(
            'saveChangesButton'
        );


    if (
        editPostForm &&
        saveChangesButton
    ) {

        editPostForm.addEventListener(
            'submit',
            function () {

                saveChangesButton.disabled =
                    true;


                saveChangesButton.textContent =
                    'Saving...';

            }
        );

    }


    /* ============================================================
       ESCAPE HTML
       Prevent HTML injection inside search result rendering
    ============================================================ */

    function escapeHtml(value) {

        const div =
            document.createElement('div');

        div.textContent =
            value ?? '';

        return div.innerHTML;

    }

});