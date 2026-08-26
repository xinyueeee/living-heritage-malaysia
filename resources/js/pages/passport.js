import { PageFlip } from 'page-flip';
import { toBlob } from 'html-to-image';

document.addEventListener('DOMContentLoaded', () => {
    /*
     * Download Cultural Journey Card as a PNG.
     */
    const journeyCard = document.querySelector(
        '[data-journey-card]'
    );

    const downloadJourneyButton = document.querySelector(
        '[data-download-journey-card]'
    );

    const waitForCardImages = async () => {
        if (!journeyCard) {
            return;
        }

        const images = Array.from(
            journeyCard.querySelectorAll('img')
        );

        await Promise.all(
            images.map((image) => {
                if (image.complete) {
                    return Promise.resolve();
                }

                return new Promise((resolve) => {
                    image.addEventListener('load', resolve, {
                        once: true,
                    });

                    image.addEventListener('error', resolve, {
                        once: true,
                    });
                });
            })
        );
    };

    const createJourneyCardBlob = async () => {
        if (!journeyCard) {
            throw new Error('Journey card was not found.');
        }

        await waitForCardImages();

        if (document.fonts?.ready) {
            await document.fonts.ready;
        }

        return toBlob(journeyCard, {
            width: 1200,
            height: 630,
            pixelRatio: 1,
            cacheBust: true,
            backgroundColor: '#f2dfc2',
        });
    };

    downloadJourneyButton?.addEventListener(
        'click',
        async () => {
            const originalText =
                downloadJourneyButton.textContent;

            downloadJourneyButton.disabled = true;
            downloadJourneyButton.textContent =
                'Creating Card...';

            try {
                const blob = await createJourneyCardBlob();

                if (!blob) {
                    throw new Error(
                        'The journey card could not be generated.'
                    );
                }

                const downloadUrl =
                    URL.createObjectURL(blob);

                const downloadLink =
                    document.createElement('a');

                downloadLink.href = downloadUrl;
                downloadLink.download =
                    'my-cultural-journey.png';

                document.body.appendChild(downloadLink);
                downloadLink.click();
                downloadLink.remove();

                URL.revokeObjectURL(downloadUrl);
            } catch (error) {
                console.error(
                    'Journey card download failed:',
                    error
                );

                alert(
                    'Sorry, the journey card could not be downloaded.'
                );
            } finally {
                downloadJourneyButton.disabled = false;
                downloadJourneyButton.textContent =
                    originalText;
            }
        }
    );

    /*
     * Available-stamp filters.
     */
    const filterButtons = document.querySelectorAll(
        '[data-stamp-filter]'
    );

    const stampCards = document.querySelectorAll(
        '[data-stamp-status]'
    );

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const selectedFilter =
                button.dataset.stampFilter;

            filterButtons.forEach((filterButton) => {
                filterButton.classList.remove('active');
            });

            button.classList.add('active');

            stampCards.forEach((card) => {
                const status =
                    card.dataset.stampStatus;

                card.hidden =
                    selectedFilter !== 'all'
                    && status !== selectedFilter;
            });
        });
    });

    /*
    * Passport stamp drag-to-reorder customization.
    */
    const stampSortList = document.querySelector(
        '[data-stamp-sort-list]'
    );

    if (stampSortList) {
        let draggedStamp = null;

        const sortableStamps = () => Array.from(
            stampSortList.querySelectorAll(
                '[data-sortable-stamp]'
            )
        );

        const findStampAfterPointer = (
            pointerX,
            pointerY
        ) => {
            const availableStamps = sortableStamps()
                .filter((stamp) => {
                    return stamp !== draggedStamp;
                });

            /*
            * First find the row currently under the pointer.
            */
            const stampsInCurrentRow =
                availableStamps.filter((stamp) => {
                    const box =
                        stamp.getBoundingClientRect();

                    return (
                        pointerY >= box.top
                        && pointerY <= box.bottom
                    );
                });

            /*
            * When the pointer is inside a row, compare its
            * horizontal position with each stamp centre.
            */
            if (stampsInCurrentRow.length > 0) {
                const stampOnRight =
                    stampsInCurrentRow.find((stamp) => {
                        const box =
                            stamp.getBoundingClientRect();

                        return pointerX
                            < box.left + (box.width / 2);
                    });

                if (stampOnRight) {
                    return stampOnRight;
                }

                /*
                * Pointer is after the last stamp in this row.
                * Insert before the first stamp in the next row.
                */
                const lastStampInRow =
                    stampsInCurrentRow[
                        stampsInCurrentRow.length - 1
                    ];

                const lastStampIndex =
                    availableStamps.indexOf(lastStampInRow);

                return availableStamps[
                    lastStampIndex + 1
                ] ?? null;
            };

            /*
            * When moving between rows, find the first stamp
            * positioned below the pointer.
            */
            return availableStamps.find((stamp) => {
                const box =
                    stamp.getBoundingClientRect();

                return pointerY
                    < box.top + (box.height / 2);
            }) ?? null;
        };

        stampSortList.addEventListener(
            'dragstart',
            (event) => {
                const stamp = event.target.closest(
                    '[data-sortable-stamp]'
                );

                if (! stamp) {
                    return;
                }

                draggedStamp = stamp;
                stamp.classList.add('is-dragging');

                event.dataTransfer.effectAllowed = 'move';

                event.dataTransfer.setData(
                    'text/plain',
                    stamp.dataset.userStampId
                );
            }
        );

        stampSortList.addEventListener(
            'dragover',
            (event) => {
                event.preventDefault();

                if (! draggedStamp) {
                    return;
                }

                event.dataTransfer.dropEffect = 'move';

                const nextStamp = findStampAfterPointer(
                    event.clientX,
                    event.clientY
                );

                if (nextStamp) {
                    stampSortList.insertBefore(
                        draggedStamp,
                        nextStamp
                    );
                } else {
                    stampSortList.appendChild(
                        draggedStamp
                    );
                }
            }
        );

        stampSortList.addEventListener(
            'drop',
            (event) => {
                event.preventDefault();
            }
        );

        stampSortList.addEventListener(
            'dragend',
            () => {
                if (draggedStamp) {
                    draggedStamp.classList.remove(
                        'is-dragging'
                    );
                }

                draggedStamp = null;
            }
        );
    }

    /*
    * Interactive draggable passport book.
    */
    const viewer = document.querySelector(
        '[data-passport-viewer]'
    );

    if (! viewer) {
        return;
    }

    const flipbookElement = viewer.querySelector(
        '[data-passport-flipbook]'
    );

    const pageElements = flipbookElement?.querySelectorAll(
        '.passport-flip-page'
    );

    if (! flipbookElement || ! pageElements?.length) {
        return;
    }

    const previousButton = viewer.querySelector(
        '[data-passport-previous]'
    );

    const nextButton = viewer.querySelector(
        '[data-passport-next]'
    );

    const pageIndicator = document.querySelector(
        '[data-passport-page-indicator]'
    );

    const pageFlip = new PageFlip(
        flipbookElement,
        {
            width: 699,
            height: 848,

            size: 'stretch',

            minWidth: 220,
            maxWidth: 430,
            minHeight: 267,
            maxHeight: 522,

            autoSize: true,
            showCover: false,
            usePortrait: true,

            drawShadow: true,
            maxShadowOpacity: 0.35,

            flippingTime: 1100,

            useMouseEvents: true,
            showPageCorners: true,
            disableFlipByClick: true,

            mobileScrollSupport: true,
            swipeDistance: 30,
        }
    );

    const updatePassportControls = () => {
        const currentPage = pageFlip.getCurrentPageIndex();
        const totalPages = pageFlip.getPageCount();
        const isPortrait =
            pageFlip.getOrientation() === 'portrait';

        const firstVisiblePage = currentPage + 1;

        const lastVisiblePage = isPortrait
            ? firstVisiblePage
            : Math.min(firstVisiblePage + 1, totalPages);

        if (pageIndicator) {
            pageIndicator.textContent = isPortrait
                ? `Page ${firstVisiblePage} of ${totalPages}`
                : `Pages ${firstVisiblePage}–${lastVisiblePage} of ${totalPages}`;
        }

        if (previousButton) {
            previousButton.disabled = currentPage <= 0;
        }

        if (nextButton) {
            nextButton.disabled =
                lastVisiblePage >= totalPages;
        }
    };

    pageFlip.on('init', updatePassportControls);
    pageFlip.on('flip', updatePassportControls);
    pageFlip.on(
        'changeOrientation',
        updatePassportControls
    );

    pageFlip.loadFromHTML(pageElements);

    previousButton?.addEventListener('click', () => {
        pageFlip.flipPrev('top');
    });

    nextButton?.addEventListener('click', () => {
        pageFlip.flipNext('top');
    });

    updatePassportControls();
});
