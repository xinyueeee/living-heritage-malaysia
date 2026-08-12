let isLoggedIn = false;
let selectedEventId = null;


function setLoginStatus(status)
{
    isLoggedIn = status;
}


async function setReminder(id)
{
    if(!isLoggedIn)
    {
        showLoginPrompt();
        return;
    }


    try
    {
        const response = await fetch('/calendar/reminder', {
            method: 'POST',

            headers: {
                'Content-Type': 'application/json',

                'X-CSRF-TOKEN':
                    document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content')
            },

            body: JSON.stringify({
                experience_id: id
            })
        });


        const data = await response.json();


        if(response.ok)
        {
            alert(data.message);
        }
        else
        {
            alert(
                data.message ||
                'Unable to set reminder.'
            );
        }
    }
    catch(error)
    {
        console.error(error);

        alert('Something went wrong.');
    }
}


function showLoginPrompt()
{
    window.location.href = '/reminder/login';
}