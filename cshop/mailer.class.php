<?

require_once('Mail.php');

class circusMailer {

    var $sendmail_path = '/usr/sbin/sendmail';

    function send($recip, $subj, $msg) {

        $headers['From']   = EMAIL_SENDER;
        $headers['BCC']    = ERROR_EMAIL_RECIP; 

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
