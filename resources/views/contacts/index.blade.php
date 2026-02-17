<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">📱 Company Directory</h1>
        
        <!-- Search -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <!-- Contacts List -->
        <div class="row">
            @forelse($contacts as $contact)
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $contact->first_name }} {{ $contact->last_name }}</h5>
                            <p class="card-text">
                                <strong>Phone:</strong> {{ $contact->phone }}<br>
                                <strong>Email:</strong> {{ $contact->email }}<br>
                                <strong>Branch:</strong> {{ $contact->branch->name ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center">No contacts found.</p>
            @endforelse
        </div>

        {{ $contacts->links() }}
    </div>
</body>
</html>
EOF
