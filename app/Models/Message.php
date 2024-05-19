<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Message {
    // Key used to crypt the message
    private const KEY = 'PT$^%&°N@$_&WZ*!Qk@$%^S*^_AMH_';

    /**
     * Decrypts function to use to decrypt BLOB informations to database
     *
     * @param $value the column to decrypt
     * @param bool $alias need alias or not
     * @return string string with concatenation done
     */
    public static function d_sql($value, $alias = true) {
        return "AES_DECRYPT($value, UNHEX(SHA2('" . self::KEY . "',512)))" . ($alias ? " $value" : " ");
    }

    /**
     * Encrypts function to use to decrypt BLOB informations to database
     *
     * @return string string with concatenation done
     */
    public static function e_sql() {
        return "AES_ENCRYPT(?, UNHEX(SHA2('" . self::KEY . "', 512)))";
    }

    /**
     * Gets all contacts with the current person.
     *
     * @return array list of contact with the current person
     */
    public static function getContacts() {
        User::updateLastAction();
        // Gets all contacts with the current user.
        $contacts = DB::table('contacts AS contact')
            ->where(function ($query) {
                $query->where('contact.mat_user1', Auth::user()->matricule)
                    ->orWhere('contact.mat_user2', Auth::user()->matricule);
            })
            ->get();

        // Select the other user but we don't know witch column to check, so we need make a test before.
        $array = [];
        foreach ($contacts as $contact) {
            $matricule = $contact->mat_user1;
            if ($contact->mat_user1 == Auth::user()->matricule) {
                $matricule = $contact->mat_user2;
            }
            $array[] = self::getContact($matricule, true);
        }
        return $array;
    }

    /**
     * Gets all information about the given contact.
     *
     * @param $value the given value of id or matricule
     * @param bool $matricule uses the matricule to find user
     * @return mixed|null the given user or null otherwise
     */
    public static function getContact($value, $matricule = false) {
        return $matricule
            ? DB::table('users')
                ->where('matricule', $value)
                ->get(['id', 'name', 'matricule', 'last_action'])->first()
            : DB::table('users')
                ->where('id', $value)
                ->get(['id', 'name', 'matricule', 'last_action'])->first();
    }

    /**
     * Checks if the current user is in contact with the given person.
     *
     * @param $value matricule of the other person
     * @param bool $matricule uses the matricule to find user
     * @return mixed|null the given user or null otherwise
     */
    public static function isContact($value, $matricule = false) {
        $contact = $matricule ? self::getContact($value, true) : self::getContact($value);
        if ($contact === null) {
            return null;
        }
        return DB::selectOne("SELECT id FROM contacts WHERE (mat_user1 = ? AND mat_user2 = ?)
                           OR (mat_user1 = ? AND mat_user2 = ?)",
            [$contact->matricule, Auth::user()->matricule, Auth::user()->matricule, $contact->matricule]);
    }

    /**
     * Checks if the current user already asked the given matricule.
     *
     * @param $matricule the given matricule to check
     * @return mixed current invitation if already asked, null otherwise
     */
    public static function isAlreayAsked($matricule) {
        return DB::selectOne("SELECT id FROM invitations WHERE mat_sender = ? AND mat_recipient = ?",
            [Auth::user()->matricule, $matricule]);
    }

    /**
     * Gets all messages in conversation
     *
     * @return array all messages found
     */
    public static function getMessages($otherUserId) {
        // Gets all contacts with the current user.
        $messages = DB::select("SELECT id, mat_sender, mat_recipient,
        " . self::d_sql("message") . ", sended_at FROM messages WHERE mat_sender = ? OR mat_recipient = ?",
            [Auth::user()->matricule, Auth::user()->matricule]);

        // Select the other user but we don't know witch column to check, so we need make a test before.
        $array = [];
        foreach ($messages as $message) {
            $elt = [];

            if ($message->mat_sender == Auth::user()->matricule) {
                $matricule = $message->mat_recipient;

                $user = DB::table('users')
                    ->where('matricule', '=', $matricule)
                    ->get(['name', 'matricule', 'id'])->first();

                if ($user->id == $otherUserId) {
                    $elt["recipient_matricule"] = $user->matricule;
                    $elt["recipient_id"] = $user->id;
                    $elt["recipient_name"] = $user->name;
                    $elt["sender_matricule"] = Auth::user()->matricule;
                    $elt["sender_id"] = Auth::user()->id;
                    $elt["sender_name"] = Auth::user()->name;
                }
            } else {
                $matricule = $message->mat_sender;

                $user = DB::table('users')
                    ->where('matricule', '=', $matricule)
                    ->get(['name', 'matricule', 'id'])->first();

                if ($user->id == $otherUserId) {
                    $elt["recipient_matricule"] = Auth::user()->matricule;
                    $elt["recipient_id"] = Auth::user()->id;
                    $elt["recipient_name"] = Auth::user()->name;
                    $elt["sender_matricule"] = $user->matricule;
                    $elt["sender_id"] = $user->id;
                    $elt["sender_name"] = $user->name;
                }
            }
            if ($user->id == $otherUserId) {
                $elt["message"] = $message->message;

                $date = date_create($message->sended_at);
                $elt["sended_at"] = date_format($date, 'd/m/Y à H:i:s');

                $array[] = $elt;
            }
        }
        return $array;
    }

    /**
     * Inserts new message in the database.
     *
     * @param $mat_sender matricule of the sender person
     * @param $mat_recipient matricule of the recipient person
     * @param $message the given message to insert in database
     */
    public static function insert($mat_sender, $mat_recipient, $message) {
        DB::insert("INSERT INTO messages (mat_sender, mat_recipient, message) VALUES (?, ?, " . self::e_sql("?") . ")",
            [$mat_sender, self::getContact($mat_recipient)->matricule, $message]);
    }

    /**
     * Make new invitation between given persons.
     *
     * @param $mat_sender matricule of the sender person
     * @param $mat_recipient matricule of the recipient person
     */
    public static function askContact($mat_sender, $mat_recipient) {
        DB::insert("INSERT INTO invitations (mat_sender, mat_recipient) VALUES (?, ?)",
            [$mat_sender, $mat_recipient]);
    }

    /**
     * Gets all invitations for the current user.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getInvitations() {
        return DB::table('invitations')
            ->where('mat_recipient', Auth::user()->matricule)
            ->join("users", "users.matricule", "=", "invitations.mat_sender")
            ->get(['users.matricule', 'users.id', 'users.name']);
    }

    /**
     * Refuses the invitation between current user and the given user.
     *
     * @param $mat_sender matricule of the sender person
     */
    public static function refuseInvit($mat_sender) {
        DB::delete("DELETE FROM invitations WHERE mat_sender = ? AND mat_recipient = ?",
            [self::getContact($mat_sender)->matricule, Auth::user()->matricule]);
    }

    /**
     * Accepts the invitation between current user and the given user.
     *
     * @param $mat_sender matricule of the sender person
     */
    public static function acceptInvit($mat_sender) {
        self::refuseInvit($mat_sender);
        DB::insert("INSERT INTO contacts (mat_user1, mat_user2) VALUES (?, ?)",
            [self::getContact($mat_sender)->matricule, Auth::user()->matricule]);
    }

    /**
     * Delete contact between two user
     *
     * @param $mat_sender matricule of the second person
     */
    public static function contactRemove($id_other) {
        $mat_other = self::getContact($id_other);
        $idContact = DB::selectOne("SELECT id FROM contacts WHERE (mat_user1 = ? AND mat_user2 = ?)
                           OR (mat_user1 = ? AND mat_user2 = ?)",
            [$mat_other->matricule, Auth::user()->matricule, Auth::user()->matricule, $mat_other->matricule]);
        DB::delete("DELETE FROM contacts WHERE id = ?", [$idContact->id]);
    }
}
