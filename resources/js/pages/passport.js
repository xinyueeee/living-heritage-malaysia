document.addEventListener('DOMContentLoaded', () => {
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
     * Passport book page flipping.
     */
    const viewer = document.querySelector(
        '[data-passport-viewer]'
    );

    if (! viewer) {
        return;
    }

    const spreads = Array.from(
        viewer.querySelectorAll('[data-book-spread]')
    );

    const previousButton = viewer.querySelector(
        '[data-passport-previous]'
    );

    const nextButton = viewer.querySelector(
        '[data-passport-next]'
    );

    const pageIndicator = document.querySelector(
        '[data-passport-page-indicator]'
    );

    let currentSpread = 0;
    let isTurning = false;

    const updateBook = (newSpread, direction) => {
        if (
            isTurning
            || newSpread < 0
            || newSpread >= spreads.length
            || newSpread === currentSpread
        ) {
            return;
        }

        isTurning = true;

        previousButton.disabled = true;
        nextButton.disabled = true;

        const currentElement = spreads[currentSpread];
        const newElement = spreads[newSpread];

        const exitClass =
            direction === 'next'
                ? 'passport-spread-exit-next'
                : 'passport-spread-exit-previous';

        const enterClass =
            direction === 'next'
                ? 'passport-spread-enter-next'
                : 'passport-spread-enter-previous';

        /*
        * First half: close the current pages.
        */
        currentElement.classList.add(exitClass);

        window.setTimeout(() => {
            currentElement.hidden = true;
            currentElement.classList.remove(exitClass);

            /*
            * Second half: open the new pages.
            */
            newElement.hidden = false;
            newElement.classList.add(enterClass);

            currentSpread = newSpread;

            const firstPage =
                (currentSpread * 2) + 1;

            const secondPage =
                firstPage + 1;

            const totalPages =
                spreads.length * 2;

            pageIndicator.textContent =
                `Pages ${firstPage}–${secondPage} of ${totalPages}`;

            window.setTimeout(() => {
                newElement.classList.remove(enterClass);

                previousButton.disabled =
                    currentSpread === 0;

                nextButton.disabled =
                    currentSpread === spreads.length - 1;

                isTurning = false;
            }, 360);
        }, 300);
    };

    previousButton?.addEventListener('click', () => {
        updateBook(
            currentSpread - 1,
            'previous'
        );
    });

    nextButton?.addEventListener('click', () => {
        updateBook(
            currentSpread + 1,
            'next'
        );
    });
});