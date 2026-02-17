<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->first()->company_name ?? 'Company' }} Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .contact-card { transition: transform 0.2s; }
        .contact-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .company-logo { max-height: 100px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Company Logo & Header -->
        <div class="text-center mb-5">
            @if($settings->first() && $settings->first()->company_logo)
                <img 
                    src="{{ asset('storage/' . $settings->first()->company_logo) }}" 
                    alt="{{ $settings->first()->company_name ?? 'Company' }} Logo" 
                    class="company-logo"
                >
            @endif
            <h1 class="display-4">
                📱 {{ $settings->first()->company_name ?? 'Company' }} Directory
            </h1>
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
        <nav aria-label="Page navigation">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($contacts->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">‹</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $contacts->previousPageUrl() }}" rel="prev">‹</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($contacts->links()->elements[0] as $page => $url)
                    @if ($page == $contacts->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($contacts->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $contacts->nextPageUrl() }}" rel="next">›</a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link">›</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
