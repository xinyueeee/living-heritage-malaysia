document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll(
        '[data-stamp-filter]'
    );

    const stampCards = document.querySelectorAll(
        '[data-stamp-status]'
    );

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const selectedFilter = button.dataset.stampFilter;

            filterButtons.forEach((filterButton) => {
                filterButton.classList.remove('active');
            });

            button.classList.add('active');

            stampCards.forEach((card) => {
                const status = card.dataset.stampStatus;

                card.hidden =
                    selectedFilter !== 'all'
                    && status !== selectedFilter;
            });
        });
    });
});