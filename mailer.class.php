<?

require_once('Mail.php');

class circusMailer {

    var $sendmail_path = '/usr/sbin/sendmail';

    function send($recip, $subj, $msg, $enc="") {

        $headers['From']   = EMAIL_SENDER;
        $headers['BCC']    = ERROR_EMAIL_RECIP; 
        if ($enc) {
            $headers['Content-type'] = 'text/plain; charset=' . $enc;
            if (strtolower($enc) == 'utf-8') {
                $subj = "=?UTF-8?B?" . base64_encode($subj) . "?=";
            }
        }

        // need this for mail(), PEAR::Mail not working
        $hdr = '';
        foreach($headers as $k => $v) {
            $hdr .= "$k: $v\n";
        }
        $hdr .= "\n";

        $params['sendmail_path'] = $this->sendmail_path;

        // Create the mail object using the Mail::factory method
        // $m =& Mail::factory('sendmail', $params); // it dont work right now

        $res = mail($recip, $subj, $msg, $hdr);
        return $res;

    }
}
