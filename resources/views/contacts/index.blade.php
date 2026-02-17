<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->first()->company_name ?? 'Company' }} Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .company-header {
            padding: 20px 0;
            text-align: center;
        }
        
        .company-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .company-tagline {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0 0;
        }
        
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .search-input {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(17, 153, 142, 0.4);
            color: white;
        }
        
        .contacts-grid {
            padding: 30px 15px;
        }
        
        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        
        .contact-name {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .contact-detail {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .contact-detail i {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .contact-detail a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .contact-detail a:hover {
            color: #667eea;
        }
        
        .branch-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination .page-link {
            border: 2px solid #e0e0e0;
            color: #667eea;
            margin: 0 3px;
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .pagination .page-link:hover {
            background: #667eea;
            border-color: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .no-contacts {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .no-contacts i {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <!-- Company Header -->
            <div class="company-header">
                @if($settings->first() && $settings->first()->company_logo)
                    <img 
                        src="{{ asset('storage/' . $settings->first()->company_logo) }}" 
                        alt="{{ $settings->first()->company_name ?? 'Company' }} Logo" 
                        class="company-logo"
                    >
                @else
                    <div style="font-size: 48px;">📱</div>
                @endif
                <h1 class="company-name">
                    {{ $settings->first()->company_name ?? 'Company' }} Directory
                </h1>
                <p class="company-tagline">
                    <i class="bi bi-people-fill"></i> Find contact information for all employees
                </p>
            </div>
            
            <!-- Search Section -->
            <div class="search-section">
                <form method="GET">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input 
                                type="text" 
                                name="search" 
                                class="form-control search-input" 
                                placeholder="🔍 Search by name, phone, or email..." 
                                value="{{ request('search') }}"
                            >
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-search me-2" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                            @if(request('search'))
                                <a href="/contacts" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="{{ route('public.contacts.print') }}" class="btn-print" target="_blank">
                        <i class="bi bi-printer-fill"></i> Print Directory
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contacts Grid -->
    <div class="container contacts-grid">
        @if($contacts->count() > 0)
            <div class="row g-4">
                @foreach($contacts as $contact)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="contact-card">
                            <div class="contact-name">
                                {{ $contact->first_name }} {{ $contact->last_name }}
                            </div>
                            
                            <div class="contact-detail">
                                <i class="bi bi-telephone-fill"></i>
                                <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>
                            </div>
                            
                            @if($contact->email)
                                <div class="contact-detail">
                                    <i class="bi bi-envelope-fill"></i>
                                    <a href="mailto:{{ $contact->email }}">
                                        {{ Str::limit($contact->email, 25) }}
                                    </a>
                                </div>
                            @endif
                            
                            @if($contact->branch)
                                <div class="contact-detail">
                                    <i class="bi bi-building"></i>
                                    <span class="branch-badge">{{ $contact->branch->name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @if($contacts->hasPages())
                <nav aria-label="Page navigation" class="mt-5">
                    {{ $contacts->withQueryString()->links('pagination::bootstrap-5') }}
                </nav>
            @endif
        @else
            <div class="no-contacts">
                <i class="bi bi-search"></i>
                <h3>No contacts found</h3>
                <p class="text-muted mb-0">Try adjusting your search criteria</p>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
