# Installation prerequisites

* "kash_checkout" and "kash_coam" modules must be installed in advance.

# Differences between "optom" and "master" branches

* Unused APIs were removed (they never should bew used in mobile application too):
    * accountedit
    * addressform
    * carriers
    * emailsubscription
    * hello
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
    * "kash_full_name" field is obligatory ("firstname" and "lastname" fields are not necessary);
    * upon registration of customer it is not logged in automatically, instead of this OTP is sent to phone, you should render the password field for the customer and keep entered phone in UI to allow repeated sbmission to the same controller which will login him.
* Integration with COAM API was added in "ps_coam" endpoint.
* Address controller was changed (the appropriate changes must be performed in the mobile application):
    * number of required fields was minimized;
    * default values were added;
    * for posting photo the following fields must be specified in mobile application, kash_photo_base64 (with photo file data encoded in base64), kash_photo_name (with original file name);
    * in fetched addresses properties two new properties, kash_photo_base64 and kash_photo_thumbnail_base64 have been added, you may use them for rendering address photos in mobile application UI.
* Addresses controller was changed to return address photos.
* Posting product comments was allowed for authenticated users only.
* Cart operations were allowed for authenticated users only.
    
# Authentication logic

Upon initial login mobile application should save OTP in its own secure part of memory. For mobile application OTP lifetime is 8 hours (for web access it is just 10 minutes).

On each subsequent request to secured API endpoints customer's phone, OTP, and cart ID must be sent in custom headers "Kash-Phone:", "Kash-Password:", "Kash-Cart-Id:".

Note: cart ID may be obtained from "session_data" parameter in API response on login. 

# Payment

Please, note that on failure COAM returns error message in Korean language which is formatted in API result in the following way, for example: 

```
{"success":false,"code":500,"message":"COAM failure. Please, check error log for details. \uce74\ub4dc\ubc88\ud638 \uc624\ub958\uc785\ub2c8\ub2e4."}
```

# Debugging and testing

Please, see test.php script at https://github.com/kashuz/optom_app/tree/master/_kash-dev/temp/rest