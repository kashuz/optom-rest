# Differences between "optom" and "master" branches

* Unused APIs were removed (they never should bew used in mobile application too):
    * accountedit
    * carriers
    * emailsubscription
    * login
    * paymentoptions
    * ps_checkpayment
    * ps_wirepayment
    * resetpasswordbyemail
    * resetpasswordcheck
    * resetpasswordemail
    * resetpasswordenter
    * setcarriercheckout
* Removed unnecessary SQL installation logic (we just not use password reset tokens). No changes in movile application are needed.
* Registration controller was changed (the appropriate changes must be performed in the mobile application):
    * "kash_phone" field is obligatory, content should match mask 010-xxxx-xxxx;
    * if "password" field is provided then login attempt is performed; if not, then further logic is performed;
    * customer is searched by "kash_phone"; if found OTP is sent and in API result "is_otp_sent" => true property is set; if not, then further logic is performed;
    * "full_name" field is obligarory ("firstname" and "lastname" fields are not necessary);
    * 
