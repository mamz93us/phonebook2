<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .contact-card { transition: transform 0.2s; }
        .contact-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-4">📱 Company Directory</h1>
            <p class="lead text-muted">Find contact information for all employees</p>
        </div>
        
        <!-- Search -->
        <form method="GET" class="mb-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group input-group-lg">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, phone, or email..." value="{{ request('search') }}">
                        <button class="btn btn-primary px-4" type="submit">Search</button>
                        @if(request('search'))
                            <a href="/contacts" class="btn btn-secondary">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <!-- Print Button -->
        <div class="text-center mb-4">
            <a href="{{ route('public.contacts.print') }}" class="btn btn-success btn-lg" target="_blank">
                🖨️ Print Directory
            </a>
        </div>

        <!-- Contacts Grid -->
        <div class="row g-4">
            @forelse($contacts as $contact)
                <div class="col-md-4 col-lg-3">
                    <div class="card contact-card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-primary">{{ $contact->first_name }} {{ $contact->last_name }}</h5>
                            <hr>
                            <p class="card-text mb-1">
                                <strong>📞 Phone:</strong><br>
                                <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>
                            </p>
                            @if($contact->email)
                                <p class="card-text mb-1">
                                    <strong>📧 Email:</strong><br>
                                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                </p>
                            @endif
                            @if($contact->branch)
                                <p class="card-text mb-0">
                                    <strong>🏢 Branch:</strong><br>
                                    {{ $contact->branch->name }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>No contacts found</h4>
                        <p class="mb-0">Try adjusting your search criteria.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($contacts->hasPages())
            <div class="mt-5 d-flex justify-content-center">
                {{ $contacts->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
