<?php

class update
{
    var $update;
    var $config;
    var $token;

    function __construct($update)
    {
        $this->update = json_decode($update, true);
        if (isset($this->update['message'])) {
            $this->type = 'message';
            $this->message = new message($this->update['message']);
            if (isset($this->message->chat)) $this->chat = $this->message->chat;
            if (isset($this->message->user)) $this->user = $this->message->user;
            if (isset($this->chat)) $this->chat->db_save();
            if ((isset($this->user) && isset($this->chat)) && ($this->chat->id == $this->user->id)) {
                $this->user->db_save();
            } else {
                $this->user->db_save('group');
            }
        } elseif (isset($this->update['edited_message'])) {
            $this->type = 'edited_message';
            $this->message = new message($this->update['edited_message']);
            if (isset($this->message->chat)) $this->chat = $this->message->chat;
            if (isset($this->message->user)) $this->user = $this->message->user;
            if (isset($this->chat)) $this->chat->db_save();
            if ((isset($this->user) && isset($this->chat)) && ($this->chat->id == $this->user->id)) {
                $this->user->db_save();
            } else {
                $this->user->db_save('group');
            }
        } elseif (isset($this->update['channel_post'])) {
            $this->type = 'channel_post';
            $this->message = new message($this->update['channel_post']);
            if (isset($this->message->chat)) $this->chat = $this->message->chat;
            $this->chat->db_save();
        } elseif (isset($this->update['edited_channel_post'])) {
            $this->type = 'edited_channel_post';
            $this->message = new message($this->update['edited_channel_post']);
            if (isset($this->message->chat)) $this->chat = $this->message->chat;
            $this->chat->db_save();
        } elseif (isset($this->update['inline_query'])) {
            $this->type = 'inline_query';
            $this->inline_query = new inline_query($this->update['inline_query']);
            if (isset($this->inline_query->user)) $this->user = $this->inline_query->user;
        } elseif (isset($this->update['callback_query'])) {
            $this->type = 'callback_query';
            $this->callback = new callback_query($this->update['callback_query']);
            if (isset($this->callback->user)) $this->user = $this->callback->user;
            if (isset($this->callback->message)) $this->message = $this->callback->message;
            if (isset($this->callback->message->chat)) $this->chat = $this->callback->message->chat;
            if (isset($this->chat)) $this->chat->db_save();
            if (isset($this->user)) $this->user->db_save();
        }
        return $this;
    }
}
