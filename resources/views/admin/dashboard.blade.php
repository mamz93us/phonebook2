@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card text-bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Branches</h5>
                <p class="card-text display-6">{{ \App\Models\Branch::count() }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Contacts</h5>
                <p class="card-text display-6">{{ \App\Models\Contact::count() }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <a href="{{ route('phonebook.xml') }}" class="btn btn-lg btn-warning w-100">
            Download XML
        </a>
    </div>
</div>
@endsection
