<?

require_once('Mail.php');

class circusMailer {

    var $sendmail_path = '/usr/sbin/sendmail';

    var $email_sender = EMAIL_SENDER;
    var $email_bcc = ERROR_EMAIL_RECIP;
    var $email_return_to = EMAIL_SENDER;
    var $encoding = null;

    function get_headers() {
        $headers = array();
        $headers['From']   = $this->email_sender;
        $headers["X-Sender"] = $this->email_sender; 
        $headers['BCC']    = $this->email_bcc;
        $headers["Return-Path"] = $this->email_return_to;  // Return path for errors

        if ($this->encoding) {
            $headers['Content-type'] = 'text/plain; charset=' . $this->encoding;
        }

        $hdr = '';
        foreach($headers as $k => $v) {
            $hdr .= "$k: $v\n";
        }
        $hdr .= "\n";
        return $hdr;
    }

    function send($recip, $subj, $msg) {

        if (strtolower($this->encoding) == 'utf-8') {
            $subj = "=?UTF-8?B?" . base64_encode($subj) . "?=";
        }

        $params['sendmail_path'] = $this->sendmail_path;

        // Create the mail object using the Mail::factory method
        // $m =& Mail::factory('sendmail', $params); // it dont work right now

        $res = mail($recip, $subj, $msg, $this->get_headers());
        return $res;

    }
}
