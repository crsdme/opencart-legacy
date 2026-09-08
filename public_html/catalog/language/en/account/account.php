<?php

$page = !empty($this->data['_account_page']) ? $this->data['_account_page'] : 'account';

// Shared
$_['text_account']        = 'Account';
$_['text_none']           = ' --- None --- ';
$_['text_confirm']        = 'Are you sure?';
$_['text_no_results']     = 'No results';
$_['text_select']         = ' --- Please Select --- ';
$_['button_view']         = 'View';
$_['button_edit']         = 'Edit';
$_['button_delete']       = 'Delete';
$_['button_new_address']  = 'New Address';
$_['button_download']     = 'Download';
$_['button_reorder']      = 'Reorder';
$_['button_return']       = 'Return';

// account/account
$_['heading_title']       = 'Account';
$_['text_my_account']     = 'My Account';
$_['text_my_orders']      = 'My Orders';
$_['text_my_affiliate']   = 'My Affiliate Account';
$_['text_my_newsletter']  = 'Newsletter';
$_['text_edit']           = 'Edit your account information';
$_['text_password']       = 'Change your password';
$_['text_address']        = 'Modify your address book entries';
$_['text_credit_card']    = 'Manage Stored Credit Cards';
$_['text_wishlist']       = 'Modify your wish list';
$_['text_order']          = 'View your order history';
$_['text_download']       = 'Downloads';
$_['text_reward']         = 'Your Reward Points';
$_['text_return']         = 'View your return requests';
$_['text_transaction']    = 'Your Transactions';
$_['text_newsletter']     = 'Subscribe / unsubscribe to newsletter';
$_['text_recurring']      = 'Recurring payments';
$_['text_affiliate_add']  = 'Register for an affiliate account';
$_['text_affiliate_edit'] = 'Edit your affiliate information';
$_['text_tracking']       = 'Custom Affiliate Tracking Code';
$_['text_logout']         = 'Logout';

// account/password
if ($page === 'password') {
	$_['heading_title']  = 'Change Password';
	$_['text_password']  = 'Enter the password you wish to use.';
	$_['text_success']   = 'Success: Your password has been successfully updated.';
	$_['entry_password'] = 'Password';
	$_['entry_confirm']  = 'Password Confirm';
	$_['error_password'] = 'Password must be between 4 and 20 characters!';
	$_['error_confirm']  = 'Password confirmation does not match password!';
}

// account/forgotten
if ($page === 'forgotten') {
	$_['heading_title']   = 'Forgot Your Password?';
	$_['text_forgotten']  = 'Forgotten Password';
	$_['text_your_email'] = 'Your E-Mail Address';
	$_['text_email']      = 'Enter the e-mail address associated with your account. Click submit to have a password reset link e-mailed to you.';
	$_['text_success']    = 'An email with a confirmation link has been sent your email address.';
	$_['entry_email']     = 'E-Mail Address';
	$_['error_email']     = 'Warning: The E-Mail Address was not found in our records, please try again!';
	$_['error_approved']  = 'Warning: Your account requires approval before you can login.';
}

// account/reset
if ($page === 'reset') {
	$_['heading_title']  = 'Reset your password';
	$_['text_password']  = 'Enter the new password you wish to use.';
	$_['text_success']   = 'Success: Your password has been successfully updated.';
	$_['entry_password'] = 'Password';
	$_['entry_confirm']  = 'Password Confirm';
	$_['error_password'] = 'Password must be between 4 and 20 characters!';
	$_['error_confirm']  = 'Password confirmation does not match password!';
	$_['error_code']     = 'Password reset code is invalid or was used previously!';
}

// account/newsletter
if ($page === 'newsletter') {
	$_['heading_title']    = 'Newsletter Subscription';
	$_['text_newsletter']  = 'Newsletter';
	$_['text_success']     = 'Success: Your newsletter subscription has been successfully updated!';
	$_['entry_newsletter'] = 'Subscribe';
}

// account/edit
if ($page === 'edit') {
	$_['heading_title']      = 'My Account Information';
	$_['text_edit']          = 'Edit Information';
	$_['text_your_details']  = 'Your Personal Details';
	$_['text_success']       = 'Success: Your account has been successfully updated.';
	$_['entry_firstname']    = 'First Name';
	$_['entry_lastname']     = 'Last Name';
	$_['entry_email']        = 'E-Mail';
	$_['entry_telephone']    = 'Telephone';
	$_['entry_fax']          = 'Fax';
	$_['error_exists']       = 'Warning: E-Mail address is already registered!';
	$_['error_firstname']    = 'First Name must be between 1 and 32 characters!';
	$_['error_lastname']     = 'Last Name must be between 1 and 32 characters!';
	$_['error_email']        = 'E-Mail Address does not appear to be valid!';
	$_['error_telephone']    = 'Telephone must be between 3 and 32 characters!';
	$_['error_custom_field'] = '%s required!';
}

// account/address
if ($page === 'address') {
	$_['heading_title']      = 'Address Book';
	$_['text_address_book']  = 'Address Book Entries';
	$_['text_address_add']   = 'Add Address';
	$_['text_address_edit']  = 'Edit Address';
	$_['text_add']           = 'Your address has been successfully inserted';
	$_['text_edit']          = 'Your address has been successfully updated';
	$_['text_delete']        = 'Your address has been successfully deleted';
	$_['text_empty']         = 'You have no addresses in your account.';
	$_['entry_firstname']    = 'First Name';
	$_['entry_lastname']     = 'Last Name';
	$_['entry_company']      = 'Company';
	$_['entry_address_1']    = 'Address 1';
	$_['entry_address_2']    = 'Address 2';
	$_['entry_postcode']     = 'Post Code';
	$_['entry_city']         = 'City';
	$_['entry_country']      = 'Country';
	$_['entry_zone']         = 'Region / State';
	$_['entry_default']      = 'Default Address';
	$_['error_delete']       = 'Warning: You must have at least one address!';
	$_['error_default']      = 'Warning: You can not delete your default address!';
	$_['error_firstname']    = 'First Name must be between 1 and 32 characters!';
	$_['error_lastname']     = 'Last Name must be between 1 and 32 characters!';
	$_['error_vat']          = 'VAT number is invalid!';
	$_['error_address_1']    = 'Address must be between 3 and 128 characters!';
	$_['error_postcode']     = 'Postcode must be between 2 and 10 characters!';
	$_['error_city']         = 'City must be between 2 and 128 characters!';
	$_['error_country']      = 'Please select a country!';
	$_['error_zone']         = 'Please select a region / state!';
	$_['error_custom_field'] = '%s required!';
}

// account/order
if ($page === 'order') {
	$_['heading_title']         = 'Order History';
	$_['text_order']            = 'Order Information';
	$_['text_order_detail']     = 'Order Details';
	$_['text_invoice_no']       = 'Invoice No.:';
	$_['text_order_id']         = 'Order ID:';
	$_['text_date_added']       = 'Date Added:';
	$_['text_shipping_address'] = 'Shipping Address';
	$_['text_shipping_method']  = 'Shipping Method:';
	$_['text_payment_address']  = 'Payment Address';
	$_['text_payment_method']   = 'Payment Method:';
	$_['text_comment']          = 'Order Comments';
	$_['text_history']          = 'Order History';
	$_['text_success']          = 'Success: You have added <a href="%s">%s</a> to your <a href="%s">shopping cart</a>!';
	$_['text_empty']            = 'You have not made any previous orders!';
	$_['text_error']            = 'The order you requested could not be found!';
	$_['column_order_id']       = 'Order ID';
	$_['column_product']        = 'No. of Products';
	$_['column_customer']       = 'Customer';
	$_['column_name']           = 'Product Name';
	$_['column_model']          = 'Model';
	$_['column_quantity']       = 'Quantity';
	$_['column_price']          = 'Price';
	$_['column_total']          = 'Total';
	$_['column_action']         = 'Action';
	$_['column_date_added']     = 'Date Added';
	$_['column_status']         = 'Status';
	$_['column_comment']        = 'Comment';
	$_['error_reorder']         = '%s is not currently available to be reordered.';
}

// account/download
if ($page === 'download') {
	$_['heading_title']     = 'Account Downloads';
	$_['text_downloads']    = 'Downloads';
	$_['text_empty']        = 'You have not made any previous downloadable orders!';
	$_['column_order_id']   = 'Order ID';
	$_['column_name']       = 'Name';
	$_['column_size']       = 'Size';
	$_['column_date_added'] = 'Date Added';
}

// account/transaction
if ($page === 'transaction') {
	$_['heading_title']      = 'Your Transactions';
	$_['column_date_added']  = 'Date Added';
	$_['column_description'] = 'Description';
	$_['column_amount']      = 'Amount (%s)';
	$_['text_transaction']   = 'Your Transactions';
	$_['text_total']         = 'Your current balance is:';
	$_['text_empty']         = 'You do not have any transactions!';
}

// account/reward
if ($page === 'reward') {
	$_['heading_title']      = 'Your Reward Points';
	$_['column_date_added']  = 'Date Added';
	$_['column_description'] = 'Description';
	$_['column_points']      = 'Points';
	$_['text_reward']        = 'Reward Points';
	$_['text_total']         = 'Your total number of reward points is:';
	$_['text_empty']         = 'You do not have any reward points!';
}

// account/return
if ($page === 'return') {
	$_['heading_title']      = 'Product Returns';
	$_['text_return']        = 'Return Information';
	$_['text_return_detail'] = 'Return Details';
	$_['text_description']   = 'Please complete the form below to request an RMA number.';
	$_['text_order']         = 'Order Information';
	$_['text_product']       = 'Product Information &amp; Reason for Return';
	$_['text_message']       = '<p>Thank you for submitting your return request. Your request has been sent to the relevant department for processing.</p><p>You will be notified via e-mail as to the status of your request.</p>';
	$_['text_return_id']     = 'Return ID:';
	$_['text_order_id']      = 'Order ID:';
	$_['text_date_ordered']  = 'Order Date:';
	$_['text_status']        = 'Status:';
	$_['text_date_added']    = 'Date Added:';
	$_['text_comment']       = 'Return Comments';
	$_['text_history']       = 'Return History';
	$_['text_empty']         = 'You have not made any previous returns!';
	$_['text_agree']         = 'I have read and agree to the <a href="%s" class="agree"><b>%s</b></a>';
	$_['text_error']         = 'The returns you requested could not be found!';
	$_['column_return_id']   = 'Return ID';
	$_['column_order_id']    = 'Order ID';
	$_['column_status']      = 'Status';
	$_['column_date_added']  = 'Date Added';
	$_['column_customer']    = 'Customer';
	$_['column_product']     = 'Product Name';
	$_['column_model']       = 'Model';
	$_['column_quantity']    = 'Quantity';
	$_['column_price']       = 'Price';
	$_['column_opened']      = 'Opened';
	$_['column_comment']     = 'Comment';
	$_['column_reason']      = 'Reason';
	$_['column_action']      = 'Action';
	$_['entry_order_id']     = 'Order ID';
	$_['entry_date_ordered'] = 'Order Date';
	$_['entry_firstname']    = 'First Name';
	$_['entry_lastname']     = 'Last Name';
	$_['entry_email']        = 'E-Mail';
	$_['entry_telephone']    = 'Telephone';
	$_['entry_product']      = 'Product Name';
	$_['entry_model']        = 'Product Code';
	$_['entry_quantity']     = 'Quantity';
	$_['entry_reason']       = 'Reason for Return';
	$_['entry_opened']       = 'Product is opened';
	$_['entry_fault_detail'] = 'Faulty or other details';
	$_['error_order_id']     = 'Order ID required!';
	$_['error_firstname']    = 'First Name must be between 1 and 32 characters!';
	$_['error_lastname']     = 'Last Name must be between 1 and 32 characters!';
	$_['error_email']        = 'E-Mail Address does not appear to be valid!';
	$_['error_telephone']    = 'Telephone must be between 3 and 32 characters!';
	$_['error_product']      = 'Product Name must be greater than 3 and less than 255 characters!';
	$_['error_model']        = 'Product Model must be greater than 3 and less than 64 characters!';
	$_['error_reason']       = 'You must select a return product reason!';
	$_['error_agree']        = 'Warning: You must agree to the %s!';
}

// account/recurring
if ($page === 'recurring') {
	$_['heading_title']             = 'Recurring Payments';
	$_['text_recurring']            = 'Recurring Payments';
	$_['text_order_recurring_id']   = 'Recurring ID:';
	$_['text_date_added']           = 'Date Added:';
	$_['text_status']               = 'Status:';
	$_['text_payment_method']       = 'Payment Method:';
	$_['text_order_id']             = 'Order ID:';
	$_['text_product']              = 'Product:';
	$_['text_quantity']             = 'Quantity:';
	$_['text_description']          = 'Description';
	$_['text_reference']            = 'Reference';
	$_['text_transaction']          = 'Transactions';
	$_['text_empty']                = 'No recurring payments found!';
	$_['text_status_active']        = 'Active';
	$_['text_status_inactive']      = 'Inactive';
	$_['text_status_cancelled']     = 'Cancelled';
	$_['text_status_suspended']     = 'Suspended';
	$_['text_status_expired']       = 'Expired';
	$_['text_status_pending']       = 'Pending';
	$_['column_order_recurring_id'] = 'Recurring ID';
	$_['column_product']            = 'Product';
	$_['column_status']             = 'Status';
	$_['column_date_added']         = 'Date Added';
	$_['column_type']               = 'Type';
	$_['column_amount']             = 'Amount';
}

// account/affiliate
if ($page === 'affiliate') {
	$_['heading_title']             = 'Your Affiliate Information';
	$_['text_affiliate']            = 'Affiliate';
	$_['text_my_affiliate']         = 'My Affiliate Account';
	$_['text_payment']              = 'Payment Information';
	$_['text_cheque']               = 'Cheque';
	$_['text_paypal']               = 'PayPal';
	$_['text_bank']                 = 'Bank Transfer';
	$_['text_success']              = 'Success: Your account has been successfully updated.';
	$_['text_agree']                = 'I have read and agree to the <a href="%s" class="agree"><b>%s</b></a>';
	$_['entry_company']             = 'Company';
	$_['entry_website']             = 'Web Site';
	$_['entry_tax']                 = 'Tax ID';
	$_['entry_payment']             = 'Payment Method';
	$_['entry_cheque']              = 'Cheque Payee Name';
	$_['entry_paypal']              = 'PayPal Email Account';
	$_['entry_bank_name']           = 'Bank Name';
	$_['entry_bank_branch_number']  = 'ABA/BSB number (Branch Number)';
	$_['entry_bank_swift_code']     = 'SWIFT Code';
	$_['entry_bank_account_name']   = 'Account Name';
	$_['entry_bank_account_number'] = 'Account Number';
	$_['error_agree']               = 'Warning: You must agree to the %s!';
	$_['error_cheque']              = 'Cheque Payee Name required!';
	$_['error_paypal']              = 'PayPal Email Address does not appear to be valid!';
	$_['error_bank_account_name']   = 'Account Name required!';
	$_['error_bank_account_number'] = 'Account Number required!';
	$_['error_custom_field']        = '%s required!';
}

// account/tracking
if ($page === 'tracking') {
	$_['heading_title']    = 'Affiliate Tracking';
	$_['text_description'] = 'To make sure you get paid for referrals you send to us we need to track the referral by placing a tracking code in the URL\'s linking to us. You can use the tools below to generate links to the %s web site.';
	$_['entry_code']       = 'Your Tracking Code';
	$_['entry_generator']  = 'Tracking Link Generator';
	$_['entry_link']       = 'Tracking Link';
	$_['help_generator']   = 'Type in the name of a product you would like to link to';
}

// account/voucher
if ($page === 'voucher') {
	$_['heading_title']    = 'Purchase a Gift Certificate';
	$_['text_voucher']     = 'Gift Certificate';
	$_['text_description'] = 'This gift certificate will be emailed to the recipient after your order has been paid for.';
	$_['text_agree']       = 'I understand that gift certificates are non-refundable.';
	$_['text_message']     = '<p>Thank you for purchasing a gift certificate! Once you have completed your order your gift certificate recipient will be sent an e-mail with details how to redeem their gift certificate.</p>';
	$_['text_for']         = '%s Gift Certificate for %s';
	$_['entry_to_name']    = 'Recipient\'s Name';
	$_['entry_to_email']   = 'Recipient\'s E-mail';
	$_['entry_from_name']  = 'Your Name';
	$_['entry_from_email'] = 'Your E-mail';
	$_['entry_theme']      = 'Gift Certificate Theme';
	$_['entry_message']    = 'Message';
	$_['entry_amount']     = 'Amount';
	$_['help_message']     = 'Optional';
	$_['help_amount']      = 'Value must be between %s and %s';
	$_['error_to_name']    = 'Recipient\'s Name must be between 1 and 64 characters!';
	$_['error_from_name']  = 'Your Name must be between 1 and 64 characters!';
	$_['error_email']      = 'E-Mail Address does not appear to be valid!';
	$_['error_theme']      = 'You must select a theme!';
	$_['error_amount']     = 'Amount must be between %s and %s!';
	$_['error_agree']      = 'Warning: You must agree that the gift certificates are non-refundable!';
}
