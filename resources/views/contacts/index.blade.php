<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->first()->company_name ?? 'Company' }} Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f5f7fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .header-section {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .company-logo {
            max-height: 70px;
            margin-bottom: 15px;
        }
        
        .company-title {
            font-size: 32px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 5px 0;
        }
        
        .company-subtitle {
            color: #7f8c8d;
            font-size: 15px;
        }
        
        .search-bar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .search-input {
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            padding: 12px 18px;
            font-size: 15px;
        }
        
        .search-input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn-search {
            background: #3498db;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            color: white;
            font-weight: 500;
        }
        
        .btn-search:hover {
            background: #2980b9;
            color: white;
        }
        
        .btn-clear {
            background: #95a5a6;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            color: white;
        }
        
        .btn-clear:hover {
            background: #7f8c8d;
            color: white;
        }
        
        .btn-print {
            background: #27ae60;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            color: white;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-print:hover {
            background: #229954;
            color: white;
        }
        
        .contact-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
            border-left: 4px solid #3498db;
        }
        
        .contact-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .contact-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        
        .contact-info {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }
        
        .contact-info strong {
            display: inline-block;
            width: 60px;
            color: #7f8c8d;
        }
        
        .contact-info a {
            color: #3498db;
            text-decoration: none;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        .branch-tag {
            display: inline-block;
            background: #ecf0f1;
            color: #555;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .pagination .page-link {
            color: #3498db;
            border: 1px solid #dfe6e9;
            border-radius: 6px;
            margin: 0 3px;
            padding: 8px 14px;
        }
        
        .pagination .page-link:hover {
            background: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        .pagination .page-item.active .page-link {
            background: #3498db;
            border-color: #3498db;
        }
        
        .no-results {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-section">
        <div class="container text-center">
            @if($settings->first() && $settings->first()->company_logo)
                <img 
                    src="{{ asset('storage/' . $settings->first()->company_logo) }}" 
                    alt="{{ $settings->first()->company_name ?? 'Company' }} Logo" 
                    class="company-logo"
                >
            @endif
            <h1 class="company-title">
                {{ $settings->first()->company_name ?? 'Company' }} Directory
            </h1>
            <p class="company-subtitle">Employee Contact Information</p>
        </div>
    </div>

    <div class="container">
        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-7">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control search-input" 
                            placeholder="Search by name, phone, or email..." 
                            value="{{ request('search') }}"
                        >
                    </div>
                    <div class="col-md-5">
                        <button class="btn btn-search me-2" type="submit">Search</button>
                        @if(request('search'))
                            <a href="/contacts" class="btn btn-clear">Clear</a>
                        @endif
                        <a href="{{ route('public.contacts.print') }}" class="btn btn-print float-end" target="_blank">
                            Print Directory
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Contacts Grid -->
        @if($contacts->count() > 0)
            <div class="row g-4 mb-5">
                @foreach($contacts as $contact)
                    <div class="col-md-6 col-lg-4">
                        <div class="contact-card">
                            <div class="contact-name">
                                {{ $contact->first_name }} {{ $contact->last_name }}
                            </div>
                            
                            <div class="contact-info">
                                <strong>Phone:</strong>
                                <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>
                            </div>
                            
                            @if($contact->email)
                                <div class="contact-info">
                                    <strong>Email:</strong>
                                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                </div>
                            @endif
                            
                            @if($contact->branch)
                                <span class="branch-tag">{{ $contact->branch->name }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @if($contacts->hasPages())
                <div class="d-flex justify-content-center mb-5">
                    {{ $contacts->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="no-results">
                <h4>No contacts found</h4>
                <p class="text-muted">Try adjusting your search</p>
            </div>
        @endif
    </div>
</body>
</html>
