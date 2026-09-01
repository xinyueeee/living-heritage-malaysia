document.addEventListener('DOMContentLoaded', function () {

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('photos');
    const previewContainer = document.getElementById('filePreview');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadCount = document.getElementById('uploadCount');
    const fileCount = document.getElementById('fileCount');
    const fileCountNumber = document.getElementById('fileCountNumber');
    const uploadForm = document.getElementById('photoUploadForm');

    /*
    |--------------------------------------------------------------------------
    | Only run on Add Photos page
    |--------------------------------------------------------------------------
    */

    if (!fileInput || !previewContainer || !uploadBtn) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Store selected files
    |--------------------------------------------------------------------------
    |
    | This array keeps files from previous selections.
    |
    | Example:
    | Select 1 → [photo1]
    | Select 2 more → [photo1, photo2, photo3]
    | Select 1 more → [photo1, photo2, photo3, photo4]
    |
    |--------------------------------------------------------------------------
    */

    let selectedFiles = [];


    /*
    |--------------------------------------------------------------------------
    | Update real file input
    |--------------------------------------------------------------------------
    */

    function updateFileInput() {

        const dataTransfer = new DataTransfer();

        selectedFiles.forEach(function (file) {
            dataTransfer.items.add(file);
        });

        fileInput.files = dataTransfer.files;
    }


    /*
    |--------------------------------------------------------------------------
    | Add files
    |--------------------------------------------------------------------------
    */

    function addFiles(files) {

        const filesArray = Array.from(files);

        filesArray.forEach(function (file) {

            // Only accept images
            if (!file.type.startsWith('image/')) {
                return;
            }

            // Avoid duplicate files
            const duplicate = selectedFiles.some(function (existingFile) {

                return (
                    existingFile.name === file.name &&
                    existingFile.size === file.size &&
                    existingFile.lastModified === file.lastModified
                );

            });

            if (!duplicate) {
                selectedFiles.push(file);
            }

        });


        // Maximum 20 photos
        if (selectedFiles.length > 20) {
            selectedFiles = selectedFiles.slice(0, 20);

            alert('You can upload a maximum of 20 photos.');
        }


        updateFileInput();

        renderPreview();
    }


    /*
    |--------------------------------------------------------------------------
    | File input change
    |--------------------------------------------------------------------------
    */

    fileInput.addEventListener('change', function (event) {

        console.log('Files selected:', event.target.files.length);

        addFiles(event.target.files);

    });


    /*
    |--------------------------------------------------------------------------
    | Drag & Drop
    |--------------------------------------------------------------------------
    */

    if (dropZone) {

        dropZone.addEventListener('dragover', function (event) {

            event.preventDefault();

            dropZone.classList.add('drag-over');

        });


        dropZone.addEventListener('dragleave', function (event) {

            event.preventDefault();

            dropZone.classList.remove('drag-over');

        });


        dropZone.addEventListener('drop', function (event) {

            event.preventDefault();

            dropZone.classList.remove('drag-over');

            addFiles(event.dataTransfer.files);

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Render Preview
    |--------------------------------------------------------------------------
    */

    function renderPreview() {

        previewContainer.innerHTML = '';


        /*
        |--------------------------------------------------------------------------
        | No files
        |--------------------------------------------------------------------------
        */

        if (selectedFiles.length === 0) {

            previewContainer.style.display = 'none';

            if (fileCount) {
                fileCount.style.display = 'none';
            }

            if (fileCountNumber) {
                fileCountNumber.textContent = '0';
            }

            if (uploadCount) {
                uploadCount.textContent = '0';
            }

            uploadBtn.innerHTML = `
                <svg width="20" height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Upload Photos
            `;

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Show count
        |--------------------------------------------------------------------------
        */

        if (fileCount) {
            fileCount.style.display = 'block';
        }

        if (fileCountNumber) {
            fileCountNumber.textContent = selectedFiles.length;
        }

        if (uploadCount) {
            uploadCount.textContent = selectedFiles.length;
        }


        /*
        |--------------------------------------------------------------------------
        | Update upload button
        |--------------------------------------------------------------------------
        */

        uploadBtn.innerHTML = `
            <svg width="20" height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Upload ${selectedFiles.length} Photos
        `;


        /*
        |--------------------------------------------------------------------------
        | Show preview
        |--------------------------------------------------------------------------
        */

        previewContainer.style.display = 'grid';


        selectedFiles.forEach(function (file, index) {

            const previewItem = document.createElement('div');

            previewItem.className = 'album-preview-item';


            /*
            |--------------------------------------------------------------------------
            | Image
            |--------------------------------------------------------------------------
            */

            const image = document.createElement('img');

            image.alt = file.name;


            const reader = new FileReader();


            reader.onload = function (event) {

                image.src = event.target.result;

            };


            reader.readAsDataURL(file);


            previewItem.appendChild(image);


            /*
            |--------------------------------------------------------------------------
            | Number
            |--------------------------------------------------------------------------
            */

            const number = document.createElement('span');

            number.className = 'album-preview-number';

            number.textContent = index + 1;

            previewItem.appendChild(number);


            /*
            |--------------------------------------------------------------------------
            | Remove button
            |--------------------------------------------------------------------------
            */

            const removeButton = document.createElement('button');

            removeButton.type = 'button';

            removeButton.innerHTML = '×';

            removeButton.setAttribute(
                'aria-label',
                `Remove ${file.name}`
            );


            removeButton.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                width: 26px;
                height: 26px;
                border: none;
                border-radius: 50%;
                background: rgba(180, 35, 35, 0.9);
                color: white;
                font-size: 18px;
                font-weight: bold;
                cursor: pointer;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
            `;


            removeButton.addEventListener('click', function (event) {

                event.preventDefault();

                event.stopPropagation();


                // Remove selected file
                selectedFiles.splice(index, 1);


                // Update input
                updateFileInput();


                // Refresh preview
                renderPreview();

            });


            previewItem.appendChild(removeButton);

            previewContainer.appendChild(previewItem);

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    */

    if (uploadForm) {

        uploadForm.addEventListener('submit', function (event) {

            if (selectedFiles.length === 0) {

                event.preventDefault();

                alert('Please select at least one photo.');

                return;
            }


            // Make sure all accumulated files are submitted
            updateFileInput();


            uploadBtn.disabled = true;

            uploadBtn.innerHTML = '⏳ Uploading...';

        });

    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CONFIRMATION
    |--------------------------------------------------------------------------
    */

    const deleteForms = document.querySelectorAll('.album-delete-form');

    deleteForms.forEach(function (form) {

        form.addEventListener('submit', function (event) {

            const message =
                form.dataset.confirm ||
                'Are you sure you want to delete this item?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }

        });

    });

});