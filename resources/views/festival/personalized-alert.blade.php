<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upcoming Experience Alert</title>
</head>

<body>

    <h2>Upcoming Experience Alert 🔔</h2>

    <p>
        An experience from one of your selected categories is coming soon.
    </p>

    <h3>{{ $experience->experiences_name }}</h3>

    <p>
        <strong>Location:</strong>
        {{ $experience->location_name }}
    </p>

    <p>
        <strong>Start Date:</strong>
        {{ $experience->start_date }}
    </p>

    <p>
        <strong>End Date:</strong>
        {{ $experience->end_date }}
    </p>

    <p>
        {{ $experience->short_description }}
    </p>

    <p>
        We thought you might be interested because this experience
        matches one of your personalized alert categories.
    </p>

    

</body>
</html>