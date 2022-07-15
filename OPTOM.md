# Installation prerequisites

* "kash_checkout" and "kash_coam" modules must be installed in advance.

# Registration logic

Workflow should be the following from the point of view of mobile app:

  a) you should show phone field in UI of mobile app;
  b) then you submit data and check results, you should pay attention to all fields of result "success", "code", "psdata"; psdata may contain string in case of failure, and array in case of success; on every step "code" may have value 500 in case of internal logic error;
  c) on this step you may get in "code" the following values, 301 - phone is absent, 302 - phone format is not valid, 200 - customer was found and OTP was sent by SMS, 303 - customer not found and names are absent in POST data (**this value 303 is particularly important for you, it means that customer not found**);
  d) if you received 200 then you should render password field for customer, if you received 303 you should render full name field for customer, in all other cases you should render error message from "psdata";
  e) on the next step you will send phone together either with full name or with password, and in result in "code" you may receive the following - all codes listed on the step C, plus 300 - in case of customer creation failure, 305 - if customer account was blocked ("Enabled" attribute was set to false in admin area), 306 - if customer not found in login logic (even if it was found earlier), 200 - in case of successful customer creation or in case of successful login.   

# Differences between "optom" and "master" branches

* Unused APIs were removed (they never should bew used in mobile application too):
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

Upon initial login mobile application should save "kash_mobile_token" received from server in its own secure part of memory. Then it can be used eternally to log in to the system.
On each subsequent request to secured API endpoints customer's phone, OTP, and cart ID must be sent in custom headers "Kash-Phone:", "Kash-Token:", "Kash-Cart-Id:".
Note: cart ID may be obtained from "session_data" parameter in API response on login. 

If user is logging in in desktop browser then mobile session is kept alive, but if user has logged in to desktop browser and then logs in via smartphone or another desktop browser, then the first desktop browser session is ended forcefully.

It is required to allow users to enter in UI passwords not less than 6 digits, or errors including word "email" in core PrestaShop logic will be generated.

# Sorting of products in category

It is done via usage of GET parameters "by" and "way". "by" may contain product attribute, "way" may contain "asc" or "desc" strings.

# Customer account update

On request with correct data OTP is sent to provided phone number. After that data must be resubmitted with OTP to perform saving.

# Localization and internationalization

Use header Kash-Lang-Iso-Code with possible values "en", "uz", "ru" to change language of text results in cart, products list, and product details.

# Payment

Please, note that on failure COAM returns error message in Korean language which is formatted in API result in the following way, for example: 

```
{"success":false,"code":500,"message":"COAM failure. Please, check error log for details. \uce74\ub4dc\ubc88\ud638 \uc624\ub958\uc785\ub2c8\ub2e4."}
```

Upon successful payment new cart ID is returned in "cart_id" property of result. You should use this cart ID for subsequent operations.

# Paynet

Order of operations to process payment:

* get services and settings for the country via "services" action;
* validate entered phone number locally and via "phoneValidator" action;
* open amounts sub-form on success;
* recalculate value in UZS upon entering it in KRW;
* on Proceed button click validate amounts via "amountValidation" action;
* on failure recalculate amounts via returned exchangeRate/divider;
* submit payment data to "payment" action;
* on failure recalculate amounts via returned exchangeRate/divider;
* on success render returned HTML code of receipt.

Logic of calculating amount in UZS: ```intval((($amount * $exchangeRate) / self::EXCHANGE_RATE_DIVIDER) / 100) * 100;```

Returned error messages must be encoded before rendering them for user on the screen. Some may contain raw texts from API, so nobody knows what is inside. Some error messages may contain linebreak symbol \n. it should be replaced with line break on rendering in UI.

Returned receipt data are also raw data from API and must be encoded before rendering in UI.

To list only Paynet orders in order history, please, use "is_paynet" => 1 in request parameters. In the list of orders, kash_paynet_receipt is returned as JSON encoded value. In order details, it is returned as array in kash_paynet_receipt_array property.

# Debugging and testing

Please, see test.php script at https://github.com/kashuz/optom_app/tree/master/_kash-dev/temp/rest