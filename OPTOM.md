# Differences between "optom" and "master" branches

* Unused APIs were removed (they never should bew used in mobile application too):
    * accountedit
    * addressform
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
    * "full_name" field is obligatory ("firstname" and "lastname" fields are not necessary);
    * upon registration of customer it is not logged in automatically, instead of this OTP is sent to phone, you should render the password field for the customer and keep entered phone in UI to allow repeated sbmission to the same controller which will login him.
* Integration with COAM API was added in "ps_coam" endpoint.
* Address controller was changed (the appropriate changes must be performed in the mobile application):
    * number of required fields was minimized;
    * default values were added;
    * for posting photo the following fields must be specified in mobile application, kash_photo_base64 (with photo file data encoded in base64), kash_photo_name (with original file name);
    * in fetched addresses properties two new properties, kash_photo_base64 and kash_photo_thumbnail_base64 have been added, you may use them for rendering address photos in mobile application UI.