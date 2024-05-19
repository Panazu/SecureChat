@extends('layouts.app')

<link href="{{ asset('css/chat.css') }}" rel="stylesheet">

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
                        <button class="btn btn-outline-success" name="submit" value="accept">Accepter</button>
                        <button class="btn btn-outline-danger" name="submit" value="refuse">Refuser</button>
                    </div>
                </div>
            </form>
        @endforeach

        <form method="POST" action="/contact/store">
            @csrf
            <div class="input-group mb-3">
                <input type="text" name="matricule" class="form-control" placeholder="Matricule à demander en contact">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary">Demander</button>
                </div>
            </div>
        </form>

        <div class="row justify-content-center">
            <div class="col-md-2">
                <div id="contacts" class="card">
                    <div class="card-header">{{ __('Contacts') }}</div>
                    @foreach($contacts as $contact)
                        <form class="remove" action="/chat/{{ $contact->id }}/delete" method="POST">
                            @csrf
                            <button>X</button>
                        </form>
                        <a href="/chat/{{ $contact->id }}">
                            <div class="contact">
                                @if (strtotime($contact->last_action) + (60 * 5) > strtotime(now()))
                                    <span class="online"></span>
                                @else
                                    <span class="offline"></span>
                                @endif
                                {{ $contact->name }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('Messages avec ' . $currentContact->name) }}</div>
                    <div class="messages"></div>

                    <div class="form-group" id="answer">
                        <form method="POST" action="/chat/{{ $currentContact->id }}/store">
                            @csrf
                            <textarea class="form-control" name="message" rows="3"></textarea>
                            <button class="btn btn-primary">Envoyer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function getRoomId() {
        return "{{ $chatRoomId }}";
    }

    function loadMessages() {
        $.getJSON("/chat/messages/" + getRoomId(), function (data, status) {
            $(".messages").empty();

            if (data.length === 0) {
                $(".messages").append($('<div>').addClass("message")
                    .append($('<div>').addClass("content").text("Aucun message pour l'instant ! Mettez le premier !"))
                );
            }

            data.forEach(function (item) {
                let user = item.sender_name + " (" + item.sender_matricule + ") - Le " + item.sended_at;
                $(".messages").append($('<div>').addClass("message")
                    .append($('<div>').addClass("user").text(user))
                    .append($('<div>').addClass("content").text(item.message)));
            });
        });
    }

    loadMessages();
    window.setInterval(loadMessages, 3000);
</script>
