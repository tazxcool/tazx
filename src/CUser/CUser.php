<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class CUser {
    private $loginStatus = false;
    private $output = null;
    private $acronym;
    
    public function __construct($acronym = null) {
        $this->acronym = $acronym;
    }
    public function ListUsersForAdminTable($db, $orderby, $order, $hits, $page) {
        $sql = "SELECT * FROM User ORDER BY $orderby $order LIMIT $hits OFFSET " . (($page - 1) * $hits) . ";";
        return $db->ExecuteSelectQueryAndFetchAll($sql, array());
    }
    // Log in user if usernamn and password is correct
    public function Login($user, $password, $db) {
        $registermessage = null;
        $sql = "SELECT acronym, name, roll, id FROM User WHERE acronym = ? AND password = md5(concat(?, salt))";
        $params = array($user, $password);
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, $params);
        if(isset($res[0])) {
            $_SESSION['user'] = $res[0];
            header('Location: admin.php');
        } else {
            $registermessage = "<p class='red'>Felaktigt användarnamn/lösenord</p>";
        }
        return $registermessage;
    } 
    // Register new user
    public function Register($user, $password, $name, $db, $roll = null) {
        $sql = "SELECT acronym FROM User WHERE acronym = ?";
        $params = array($user);
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, $params);
        if(!isset($res[0])) {
            $registermessage = CUser::SaveUser($user, $password, $name, $db, $roll);
        } else { 
            $registermessage = "<p class='red'>Användarnamnet tyvärr upptaget, välj annat användarnamn</p>";
        }
        return $registermessage;
    }

    public function SaveUser($user, $password, $name, $db, $roll = null) {
        if ($roll){
            $sql = "
                INSERT INTO User (acronym, name, salt, roll) VALUES 
                (?, ?, unix_timestamp(), ?);
                ";
            $params = array($user, $name, $roll);
        } else{
            $sql = "
                INSERT INTO User (acronym, name, salt) VALUES 
                (?, ?, unix_timestamp());
                ";
            $params = array($user, $name);
        }
        $res = $db->ExecuteQuery($sql, $params);
        if($res) {
            $sql = "
                UPDATE User SET password = md5(concat(?, salt)) WHERE acronym = ?;
                ";
            $params = array($password, $user);
            $res = $db->ExecuteQuery($sql, $params);
            if($res) {
                if (!$roll) {
                    $_SESSION['registermessage'] = "<p class='green'> Registrering godkänd, vänligen logga in.</p>";
                    header('Location: login.php');
                } else {
                    $registermessage  = "<p class='green'> Användare tillagd!</p>";
                }
            } else {
                $registermessage = "<p class='red'> Problem uppstod med lösenordet</p>";
            }
        } else {
            $registermessage = "<p class='red'>Uppgifterna kunde INTE registreras</p>";
        }
        return $registermessage;
    }
    
    // prints form for login
    public function ShowLoginForm() {
        CUser::IsAuthenticated();
        $htmlform = <<<EOD
            <form method=post>
                <fieldset>
                    <legend>Login</legend>
                    <p><em>Du kan logga in med doe:doe eller admin:admin.</em></p>
                    <p><label>Användare:<br/><input type='text' name='acronym' value=''/></label></p>
                    <p><label>Lösenord:<br/><input type='password' name='password' value=''/></label></p>
                    <p><input type='submit' name='login' value='Login'/></p>
                    <p><a href='register.php'>Ny användare?</a></p>
                    <output>{$this->output}</output>
                </fieldset>
            </form>
EOD;
        return $htmlform;
  }
    // prints form for register new user
    public function ShowRegisterForm($admin=null) {
        $roll = ($admin == 'admin')? "<p><label>Rättighet:<br/><select name='roll'><option value='admin'>Admin</option><option value='user' selected>User</option></select></label></p>": null;
        $htmlform = <<<EOD
            <form method=post>
                <fieldset>
                    <legend>Registrering av ny användare</legend>
                    {$roll}
                    <p><label>Användarnamn:<br/><input type='text' name='acronym' value='' required/></label></p>
                    <p><label>Lösenord:<br/><input type='password' name='password' value='' required/></label></p>
                    <p><label>Namn:<br/><input type='text' name='name' value='' required/></label></p>
                    <p><input type='submit' name='register' value='Skapa'/></p>
                </fieldset>
            </form>
EOD;
        return $htmlform;
    }
    public function ShowDeleteProfileForm($db, $acronym) {
        $htmlform = <<<EOD
            <form method=post>
                <fieldset>
                    <legend>Borttagning av användare</legend>
                    <p><label>Användarnamn:<br/><input type='text' name='acronym' value='{$acronym->acronym}' readonly/></label></p>
                    <p><label>Rättighet:<br/><input type='text' name='roll' value='{$acronym->roll}' readonly/></label></p>
                    <p><label>Namn:<br/><input type='text' name='name' value='{$acronym->name}' readonly/></label></p>
                    <p><input type='submit' name='delete' value='Ta bort'/></p>
                </fieldset>
            </form>
EOD;
        return $htmlform;            
    }
    public function GetProfile($acronym, $db) {
        $sql = "SELECT * FROM User WHERE acronym = ?";
        $params = array($acronym);
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, $params);
        if(isset($res[0])) {
            $htmlform = $this->ShowProfile($res);
        } else{
            $htmlform = "<p class='red'>Gick inte att hämta uppgifter från databasen</p>";
        }
        return $htmlform;
    }
    public function GetProfileById($id, $db, $acronym, $edit=null) {
        $sql = "SELECT * FROM User WHERE id = ?";
        $params = array($id);
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, $params);

        if (!$res) {
            $htmlform = false;
        } else if ($edit == 'edit') {
            if(isset($res[0])) {
                $_SESSION['userAcronym'] = $res[0];
                $htmlform = $this->ShowProfile($res, $acronym);
            } else{
                $htmlform = "<p class='red'>Valt id är inte giltigt!</p>";
            }
            
        } else {
            $htmlform = $res[0];
        }
        return $htmlform;
    }
    
    public function CheckProfileById($id, $db, $acronym) {
        $sql = "SELECT * FROM User WHERE id = ?";
        $params = array($id);
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, $params);
        if (!$res) {
            return "<p class='red'>Valt id är inte giltigt!</p>";
        } else if ($res[0]->acronym == $acronym->acronym){
            return "<p class='red'>Inloggat konto kan EJ tas bort, logga in med annan användare för att ta bort detta konto!</p>";
        } else {
            return false;
        }
    }
      // prints form for profile of user
    public function ShowProfile($res, $acronym = null) {
        $userid = null;
        if ($acronym) {
            if ($acronym->roll == 'admin' && $acronym->id != $res[0]->id) {
                $adminSelected = $res[0]->roll == 'admin' ? 'selected' : null;
                $userSelected = $res[0]->roll == 'user' ? 'selected' : null;
                $rollData = ($acronym->roll == 'admin')? "<p><label>Rättighet:<br/><select name='roll'><option value='admin' {$adminSelected}>Admin</option><option value='user' {$userSelected}>User</option></select></label></p>": $rollData = "<p><label>Rättighet:<br/><input type='text' name='roll' value='{$res[0]->roll}' readonly disabled/></label></p>";
                
            } else {
                $rollData = "<p><label>Rättighet:<br/><input type='hidden' name='roll' value='{$res[0]->roll}'/><input type='text' name='rollDisplay' value='{$res[0]->roll}' readonly disabled/></label></p>";
            }
            $userid = "&id=" . $res[0]->id;
        } else {
            $rollData = "<p><label>Rättighet:<br/><input type='text' name='roll' value='{$res[0]->roll}' readonly disabled/></label></p>";
        }
  
        $htmlform = <<<EOD
            <form method=post>
                <fieldset>
                    <legend>Användaruppgifter</legend>
                    <input type='hidden' name='acronym' value='{$res[0]->acronym}'>
                    <p><label>Användarnamn:<br/><input type='text' name='acronymDisplay' value='{$res[0]->acronym}' readonly disabled/></label></p>
                    {$rollData}
                    <p><label>Namn:<br/><input type='text' name='name' value='{$res[0]->name}' required/></label></p>
                    <p><a href='?newPassword{$userid}'>Byt lösenord</a></p>
                    <p><input type='submit' name='updateProfile' value='Uppdatera'/></p>
                </fieldset>
            </form>
EOD;
        return $htmlform;
    }
    public function SetNewPassword($db, $password, $verifiedPassword, $acronym) {
        if ($password === $verifiedPassword) {
            $sql = "UPDATE User SET password = md5(concat(?, salt)) WHERE acronym = ?;";
            $params = array($password, $acronym);
            $res = $db->ExecuteQuery($sql, $params);
            if($res) {
                $registermessage = "<p class='green'>Uppgifterna uppdaterade!</p>";
            } else{
                $registermessage = "<p class='red'>Gick inte att updatera uppgifterna i databasen</p>";
            }
        } else {
            $registermessage = "<p class='red'>De två lösenorden matchar inte varandra!</p>";
        }
        
        return $registermessage;
    }
    // prints form for profile of user
    public function ShowPasswordForm($acronym) {
        $htmlform = <<<EOD
            <form method=post>
                <fieldset>
                    <legend>Användaruppgifter</legend>
                    <input type='hidden' name='acronym' value='{$acronym->acronym}'>
                    <p><label>Nytt lösenord:<br/><input type='password' name='password' value='' required/></label></p>
                    <p><label>Verifiera lösenordet:<br/><input type='password' name='verifiedPassword' value='' required/></label></p>
                    <p><input type='submit' name='updatePassword' value='Uppdatera'/></p>
                </fieldset>
            </form>
EOD;
        return $htmlform;
    }
    public function UpdateProfile($db, $acronym, $name, $roll=null) {
        if ($roll) { 
            $sql = "UPDATE User SET roll = ? WHERE acronym = ?";
            $params = array($roll,$acronym);
            $res = $db->ExecuteQuery($sql, $params);
        }
        
            $sql = "UPDATE User SET name = ? WHERE acronym = ?";
            $params = array($name,$acronym);
            $res = $db->ExecuteQuery($sql, $params);
        
        if($res) {
            $registermessage = "<p class='green'>Uppgifterna uppdaterade!</p>";
        } else{
            $registermessage = "<p class='red'>Gick inte att updatera uppgifterna i databasen</p>";
        }
        return $registermessage;
    }
    public function DeleteProfile($db, $acronym) {

        $sql = "DELETE FROM User WHERE acronym = ? LIMIT 1";
        $params = array($acronym);
        $res = $db->ExecuteQuery($sql, $params);
        if($res) {
            $registermessage = "<p class='green'>Användaren togs bort!</p>";
        } else{
            $registermessage = "<p class='red'>Gick inte att ta bort uppgifterna i databasen</p>";
        }
        return $registermessage;
    }
    // Logs user out
    public function Logout() {
        unset($_SESSION['user']);
        header('Location: login.php');
    }
    
    // prints form for logout
    public function ShowLogoutForm() {
        $htmlform = <<<EOD
        <form method=post>
            <fieldset>
                <legend>Logout</legend>
                <p><input type='submit' name='logout' value='logout'/></p>
                
                <output>{$this->output}</output>
            </fieldset>
        </form>
EOD;
        return $htmlform;
    }
                
    // sets values to variable output
    public function IsAuthenticated() {
        $this->output = "<b>";
        if($this->acronym) {
            $this->output .= "Du är inloggad som: $this->acronym ({$_SESSION['user']->name})";
        }
        else {
          $this->output .= "Du är INTE inloggad.";
        }
        $this->output .= "</b>";
        return $this->output;
    } 
    
    // returns the users acronym.
    public function GetAcronym() {
        return $this->acronym;
    } 
    
    // returns the users name
    public function GetName() {
        if (isset($_SESSION['user'])) {
            return $_SESSION['user']->name;
        } else {
            return "Namn saknas";
        }
    } 
}


