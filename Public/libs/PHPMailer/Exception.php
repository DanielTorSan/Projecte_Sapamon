<?php

/**
 * PHPMailer Exception class.
 * PHP Version 5.5.
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 *
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer exception handler.
 *
 * @author Marcus Bointon <phpmailer@synchromedia.co.uk>
 */
class Exception extends \Exception
{
    /**
     * Prettify error message output.
     *
     * @return string
     */
    public function errorMessage()
    {
        $message = $this->getMessage();
        $error = '<strong>' . htmlspecialchars($message, ENT_COMPAT | ENT_HTML401) . "</strong><br />\n";
        
        // Add specific suggestions for common PHPMailer errors
        if (strpos($message, 'SMTP Error: Could not authenticate') !== false) {
            $error .= '<br><strong>Suggeriments de solució:</strong><br>';
            $error .= '- Verifica que l\'usuari i contrasenya SMTP siguin correctes<br>';
            $error .= '- Si utilitzes Gmail, és necessari utilitzar una "Contrasenya d\'aplicació" i no la teva contrasenya normal<br>';
            $error .= '- Com crear una contrasenya d\'aplicació a Google: <a href="https://support.google.com/accounts/answer/185833" target="_blank">Veure instruccions</a><br>';
            $error .= '- Comprova que la teva compte de Gmail no tingui la verificació en dos passos sense contrasenya d\'aplicació<br>';
            $error .= '- Prova a desactivar temporalment qualsevol antivirus o firewall que pugui estar bloquejant la connexió<br>';
        } elseif (strpos($message, 'SMTP connect() failed') !== false) {
            $error .= '<br><strong>Suggeriments de solució:</strong><br>';
            $error .= '- Comprova la teva connexió a Internet<br>';
            $error .= '- Verifica que el servidor SMTP (smtp.gmail.com) sigui correcte<br>';
            $error .= '- Comprova que el port (587 per TLS o 465 per SSL) no estigui bloquejat pel firewall<br>';
            $error .= '- Prova a utilitzar un altre servei de correu si el problema persisteix<br>';
        }
        
        return $error;
    }
}
