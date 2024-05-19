<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddContact;
use App\Http\Requests\AddMessage;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller {

    /**
     * Checks the user is connected to access theses pages.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Shows the index without messages and with any choose of discussion.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index() {
        $contacts = Message::getContacts();
        $invitations = Message::getInvitations();
        return view('home', compact('contacts', 'invitations'));
    }

    /**
     * Shows a discussion with someone
     *
     * @param Request $request the given request with all informations
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show(Request $request) {
        if (!isset($request->id_other_contact) || !Message::isContact($request->id_other_contact)) {
            abort(404);
        }
        $invitations = Message::getInvitations();
        $currentContact = Message::getContact($request->id_other_contact);
        $contacts = Message::getContacts();
        $chatRoomId = $request->id_other_contact;
        return view('chat', compact('chatRoomId', 'contacts', 'currentContact', 'invitations'));
    }

    /**
     * Deletes contact between two persons
     */
    public function contactRemove(Request $request) {
        if (!isset($request->id_other_contact) || !Message::isContact($request->id_other_contact)) {
            abort(404);
        }
        Message::contactRemove($request->id_other_contact);
        return redirect("/home")->with('status', 'Contact correctement supprimé !');
    }

    /**
     * Makes the requests to add new messages and checks.
     *
     * @param AddMessage $request the given request with all informations
     * @return \Illuminate\Http\RedirectResponse
     */
    public function messageStore(AddMessage $request) {
        if (!isset($request->id_other_contact) || !Message::isContact($request->id_other_contact)) {
            abort(404);
        }
        Message::insert(Auth::user()->matricule, $request->id_other_contact, $request->message);
        return redirect()->back()->with('status', 'Message correctement ajouté !');
    }

    /**
     * Accepts or refuses the new ask of contact.
     *
     * @param AddContact $request the given request with all informations
     * @return \Illuminate\Http\RedirectResponse
     */
    public function contactStore(AddContact $request) {
        if (!isset($request->matricule)) {
            abort(404);
        }

        if (!Message::getContact($request->matricule, true)) {
            return redirect()->back()->with('error', 'Cette personne n\'existe pas...');
        }

        if (Message::isContact($request->matricule, true)) {
            return redirect()->back()->with('error', 'Vous êtes déjà en contact avec cette personne.');
        }

        if (Message::isAlreayAsked($request->matricule)) {
            return redirect()->back()->with('error', 'Vous avez déjà demandé à vous abonner à cette personne.');
        }

        if ($request->matricule == Auth::user()->matricule) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas vous envoyer d\'invitation');
        }

        Message::askContact(Auth::user()->matricule, $request->matricule);
        return redirect()->back()->with('status', 'Demande correctement envoyée !');
    }

    /**
     * Sends the new invitation to make contact with someone.
     *
     * @param Request $request the given request with all informations
     * @return \Illuminate\Http\RedirectResponse
     */
    public function invitStore(Request $request) {
        if (!isset($request->id_sender) || Message::isContact($request->id_sender)) {
            abort(404);
        }

        if (!Message::getContact($request->id_sender)) {
            return redirect()->back()->with('error', 'Cette personne n\'existe pas...');
        }

        if ($request->submit == "accept") {
            Message::acceptInvit($request->id_sender);
            return redirect()->back()->with('status', 'Invitation correctement acceptée !');
        } else {
            Message::refuseInvit($request->id_sender);
            return redirect()->back()->with('status', 'Invitation correctement supprimée !');
        }
    }

    /**
     * Gets all messages in json response
     *
     * @param Request $request the given request with all informations
     * @return \Illuminate\Http\JsonResponse
     */
    public function messagesJson(Request $request) {
        if (!isset($request->id_other_contact) || !Message::isContact($request->id_other_contact)) {
            abort(404);
        }
        $messages = Message::getMessages($request->id_other_contact);
        return response()->json($messages);
    }
}
