document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | COMMENT BUTTON
    |--------------------------------------------------------------------------
    | Click the comment icon to show / hide comments.
    */

    document.addEventListener('click', function (event) {

        const commentButton = event.target.closest('.comment-toggle');

        if (!commentButton) {
            return;
        }

        const postId = commentButton.dataset.postId;

        const commentSection =
            document.getElementById('comments-' + postId);

        if (!commentSection) {
            console.error(
                'Comment section not found for post:',
                postId
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | TOGGLE
        |--------------------------------------------------------------------------
        */

        commentSection.classList.toggle('comments-hidden');

        const isOpen =
            !commentSection.classList.contains('comments-hidden');

        /*
        |--------------------------------------------------------------------------
        | ARIA
        |--------------------------------------------------------------------------
        */

        commentButton.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

    });


    /*
    |--------------------------------------------------------------------------
    | COMMENT FORM SUBMISSION
    |--------------------------------------------------------------------------
    | Submit comment without refreshing the whole page.
    */

    document.addEventListener('submit', async function (event) {

        const form = event.target.closest('.comment-form');

        if (!form) {
            return;
        }

        event.preventDefault();

        const input = form.querySelector(
            'input[name="comment"]'
        );

        const submitButton =
            form.querySelector('.comment-submit-btn');

        const commentsList =
            form.closest('.comment-section')
                ?.querySelector('.comments-list');

        if (!input || !submitButton) {
            return;
        }

        const commentText = input.value.trim();

        if (!commentText) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DISABLE BUTTON WHILE SUBMITTING
        |--------------------------------------------------------------------------
        */

        submitButton.disabled = true;
        submitButton.textContent = 'Posting...';

        try {

            const response = await fetch(
                form.action,
                {
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.getAttribute('content'),

                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    body: new FormData(form)
                }
            );


            /*
            |--------------------------------------------------------------------------
            | CHECK RESPONSE
            |--------------------------------------------------------------------------
            */

            if (!response.ok) {

                throw new Error(
                    'Failed to submit comment.'
                );

            }


            const data = await response.json();


            /*
            |--------------------------------------------------------------------------
            | ADD NEW COMMENT TO LIST
            |--------------------------------------------------------------------------
            */

            if (commentsList) {

                const emptyMessage =
                    commentsList.querySelector('.no-comments');

                if (emptyMessage) {
                    emptyMessage.remove();
                }


                const commentItem =
                    document.createElement('div');

                commentItem.className =
                    'comment-item';


                commentItem.innerHTML = `

                    <img
                        src="${data.user?.profile_photo ?? '/images/default-avatar.png'}"
                        class="comment-avatar"
                        alt="Avatar"
                    >

                    <div class="comment-content">

                        <strong>
                            ${escapeHtml(
                                data.user?.user_name ?? 'Anonymous'
                            )}
                        </strong>

                        <p>
                            ${escapeHtml(
                                data.comment ?? commentText
                            )}
                        </p>

                        <small>
                            Just now
                        </small>

                    </div>

                `;


                commentsList.appendChild(
                    commentItem
                );

            }


            /*
            |--------------------------------------------------------------------------
            | CLEAR INPUT
            |--------------------------------------------------------------------------
            */

            input.value = '';


        } catch (error) {

            console.error(
                'Comment error:',
                error
            );

            alert(
                'Unable to add comment. Please try again.'
            );

        } finally {

            submitButton.disabled = false;

            submitButton.textContent = 'Post';

        }

    });


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    | Prevent user-entered comment text from being inserted as HTML.
    */

    function escapeHtml(text) {

        const div =
            document.createElement('div');

        div.textContent = text;

        return div.innerHTML;

    }

});