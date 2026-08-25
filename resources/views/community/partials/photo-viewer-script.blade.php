<script>

document.addEventListener('DOMContentLoaded', function () {


    /* ===================================================
       PHOTO VIEWER ELEMENTS
    =================================================== */

    const photoViewer =
        document.getElementById('photoViewer');

    const photoViewerImage =
        document.getElementById('photoViewerImage');

    const photoViewerCounter =
        document.getElementById('photoViewerCounter');

    const closeButton =
        document.getElementById('photoViewerClose');

    const prevButton =
        document.getElementById('photoViewerPrev');

    const nextButton =
        document.getElementById('photoViewerNext');

    if (!photoViewer) {
        return;
    }



    /* ===================================================
       PHOTO VIEWER STATE
    =================================================== */

    let currentImages = [];

    let currentIndex = 0;



    /* ===================================================
       OPEN PHOTO VIEWER

       Uses delegation on document.body (instead of binding
       to each .gallery-item at load time) so it also works
       for galleries added later, e.g. cloned into the post
       detail modal on the Saved Posts page.
    =================================================== */

    document.body.addEventListener(
        'click',
        function (event) {


            const item =
                event.target.closest('.gallery-item');

            if (!item) {
                return;
            }


            const gallery =
                item.closest('.post-gallery');

            if (!gallery) {
                return;
            }


            currentImages =
                JSON.parse(
                    gallery.dataset.images
                );


            currentIndex =
                parseInt(
                    item.dataset.index
                );


            openPhotoViewer();

        }
    );



    /* ===================================================
       OPEN
    =================================================== */

    function openPhotoViewer() {


        if (currentImages.length === 0) {

            return;

        }


        photoViewer.classList.add('active');


        updatePhotoViewer();


        document.body.style.overflow =
            'hidden';

    }



    /* ===================================================
       UPDATE PHOTO
    =================================================== */

    function updatePhotoViewer() {


        photoViewerImage.src =
            currentImages[currentIndex];

        photoViewerImage.hidden = false;


        photoViewerCounter.textContent =
            `${currentIndex + 1} / ${currentImages.length}`;


        if (currentImages.length <= 1) {


            prevButton.style.display =
                'none';

            nextButton.style.display =
                'none';


        } else {


            prevButton.style.display =
                'flex';

            nextButton.style.display =
                'flex';

        }

    }



    /* ===================================================
       CLOSE
    =================================================== */

    function closePhotoViewer() {


        photoViewer.classList.remove(
            'active'
        );


        document.body.style.overflow =
            '';


        photoViewerImage.removeAttribute('src');

        photoViewerImage.hidden = true;

    }


    closeButton.addEventListener(
        'click',
        function () {

            closePhotoViewer();

        }
    );



    /* ===================================================
       PREVIOUS
    =================================================== */

    prevButton.addEventListener(
        'click',
        function (event) {


            event.stopPropagation();


            currentIndex--;


            if (currentIndex < 0) {

                currentIndex =
                    currentImages.length - 1;

            }


            updatePhotoViewer();

        }
    );



    /* ===================================================
       NEXT
    =================================================== */

    nextButton.addEventListener(
        'click',
        function (event) {


            event.stopPropagation();


            currentIndex++;


            if (
                currentIndex >=
                currentImages.length
            ) {

                currentIndex = 0;

            }


            updatePhotoViewer();

        }
    );



    /* ===================================================
       CLICK BACKGROUND TO CLOSE
    =================================================== */

    photoViewer.addEventListener(
        'click',
        function (event) {


            if (
                event.target ===
                photoViewer
            ) {

                closePhotoViewer();

            }

        }
    );



    /* ===================================================
       KEYBOARD CONTROLS
    =================================================== */

    document.addEventListener(
        'keydown',
        function (event) {


            if (
                !photoViewer.classList.contains(
                    'active'
                )
            ) {

                return;

            }



            /* ESC */

            if (event.key === 'Escape') {

                closePhotoViewer();

            }



            /* LEFT */

            if (event.key === 'ArrowLeft') {


                currentIndex--;


                if (currentIndex < 0) {

                    currentIndex =
                        currentImages.length - 1;

                }


                updatePhotoViewer();

            }



            /* RIGHT */

            if (event.key === 'ArrowRight') {


                currentIndex++;


                if (
                    currentIndex >=
                    currentImages.length
                ) {

                    currentIndex = 0;

                }


                updatePhotoViewer();

            }

        }
    );


});

</script>
