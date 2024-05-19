@extends('layouts.app')

<link href="{{ asset('css/home.css') }}" rel="stylesheet">

@section('content')
    <div class="container">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @foreach($invitations as $invitation)
            <form method="POST" action="/invit/{{ $invitation->id }}/store">
                @csrf
                <div class="input-group mb-3">
                    <input type="text" disabled name="text" class="form-control"
                           placeholder="{{ $invitation->name }} vous a demandé en contact !">
                    <div class="input-group-append">
                        <button class="btn btn-outline-success" name="submit" value="accept" type="submit">Accepter
                        </button>
                        <button class="btn btn-outline-danger" name="submit" value="refuse" type="submit">Refuser
                        </button>
                    </div>
                </div>
            </form>
        @endforeach

        <form method="POST" action="/contact/store">
            @csrf
            <div class="input-group mb-3">
                <input type="text" name="matricule" class="form-control" placeholder="Matricule à demander en contact">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Demander</button>
                </div>
            </div>
        </form>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div id="contacts" class="card">
                    <div class="card-header">{{ __('Contacts') }}</div>

                    @if(empty($contacts))
                        <div class="contact">
                            Aucun contact pour l'instant, veuillez en rajouter.
                        </div>
                    @endif

                    @foreach($contacts as $contact)
                        <a href="/chat/{{ $contact->id }}">
                            <div class="contact">
                                {{ $contact->name }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
