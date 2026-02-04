<?php

declare(strict_types=1);

return [
    'file' => [
        'corrupted' => 'The requested file is corrupted or unreadable.',
        'unstorable' => 'The requested file cannot be stored securely.',
    ],
    'auth' => [
        'unauthenticated' => 'You must be logged in to access this resource.',
        'forbidden' => 'You do not have permission to access this resource.',
        'deactivated' => 'Your account has been deactivated. Please contact support for assistance.',
        'registration' => [
            'failed' => 'Registration failed. Please try again later.',
            'token_generation' => [
                'failed' => 'Failed to generate authentication token. Please try logging in.',
            ],
        ],
        'login' => [
            'failed' => 'The provided credentials do not match our records.',
            'unauthorized' => 'You are not authorized to access the admin panel.',
            'proceed_to_register' => 'No account found with the provided credentials. Please proceed to register.',
        ],
        'otp' => [
            'invalid' => 'The provided OTP is invalid or has expired.',
            'send_failed' => 'Failed to send OTP. Please try again later.',
            'sent_to' => 'We have sent an OTP to :username.',
            'sms' => 'Your :app_name verification code is: :otp.',
        ],
        'throttle' => 'Too many attempts. Please try again in :date.',
        'verification' => [
            'already_verified' => 'Your account has already been verified.',
            'success' => 'Your account has been successfully verified.',
            'failed' => 'Verification failed. Please check the OTP and try again.',
            'resend_success' => 'A new verification OTP has been sent to your username.',
            'resend_failed' => 'Failed to resend verification OTP. Please try again later.',
        ],
    ],
    'validation' => [
        'phone' => 'Please use a valid Philippine phone number.',
    ],
    'profile_update' => [
        'success' => 'Profile Updated Successfully.',
        'error' => 'Fail to Update Profile',
    ],
    'questionnaire' => [
        'question' => [
            'required' => 'Each question must have text.',
            'type_invalid' => 'Each question must have a valid type.',
        ],
        'answer' => [
            'value_required' => 'Each answer must have a value unless the question type is text.',
        ],
        'no_questions' => 'The questionnaire must contain at least one question.',
        'creation_failed' => 'Failed to create the questionnaire. Please try again later.',
        'creation_success' => 'Questionnaire created successfully.',
        'update_failed' => 'Failed to update the questionnaire. Please try again later.',
        'update_success' => 'Questionnaire updated successfully.',
        'unauthorized' => [
            'access' => 'You are not authorized to access this questionnaire.',
            'modify' => 'You are not authorized to modify this questionnaire.',
            'delete' => 'You are not authorized to delete this questionnaire.',
        ],
        'none_selected' => 'No questionnaire selected.',
        'deletion_failed' => 'Failed to delete the questionnaire. Please try again later.',
        'deletion_success' => 'Questionnaire deleted successfully.',
    ],
    'job_wizard' => [
        'publish_success' => 'Job vacancy published successfully.',
        'draft_not_found' => 'No draft job vacancy found for the current operation.',
        'save_failed' => 'Failed to save the job vacancy step. Please try again later.',
        'confirmation' => [
            'create' => 'Job post will first be reviewed before it goes live. Do you want to proceed?',
            'update' => 'Updating the job post will require another review before it goes live again. This means the job post will be temporarily unpublished. Do you want to proceed?',
        ],
    ],
    'form' => [
        'submission_success' => 'Form submitted successfully.',
        'submission_failed' => 'Form submission failed. Please try again later.',
    ],
    'server' => [
        'error' => 'An unexpected server error occurred. Please try again later.',
    ],
    'sms' => [
        'send_failed' => 'Failed to send SMS. Please try again later.',
    ],
    'chat' => [
        'session_not_found' => 'Chat session not found for the selected offer.',
        'message_not_sent' => 'Failed to send the message. Please try again later.',
        'attachment_sent' => 'Attachment sent.',
        'call_initiated' => 'Call initiated.',
        'call_failed' => 'Failed to initiate the call. Please try again later.',
        'call_invalid' => 'Call invalid. Please try again later.',
        'call_ended' => 'Call ended.',
    ],
    'complaint' => [
        'new_complaint_notification' => [
            'title' => 'New Complaint Submitted',
            'body' => ':user_name has submitted a new complaint regarding :subject.',
        ],
    ],
    'payment' => [
        'new_payment_notification' => [
            'title' => 'New Payment Received',
            'body' => 'A new payment of :amount :currency has been received from :user_name.',
        ],
        'invoice' => [
            'new_invoice_notification' => [
                'title' => 'New Invoice Created',
                'body' => 'A new invoice #:invoice_number has been created with a total of :total :currency.',
            ],
        ],
        'payment_approved' => [
            'title' => 'Payment Approved',
            'body' => 'Your payment of :amount :currency has been approved. Thank you for your payment.',
        ],
        'payment_rejected' => [
            'title' => 'Payment Rejected',
            'body' => 'Your payment of :amount :currency has been rejected. Please contact support for more information.',
        ],
        'make_payment' => [
            'failed' => 'Failed to process the payment. Please try again later.',
            'success' => 'Payment processed successfully.',
        ],
    ],
    'expert' => [
        'profile' => [
            'update_failed' => 'Failed to update profile. Please try again later.',
            'update_success' => 'Profile updated successfully.',
        ],
        'service' => [
            'creation_failed' => 'Failed to create the service. Please try again later.',
            'creation_success' => 'Service created successfully.',
            'update_failed' => 'Failed to update the service. Please try again later.',
            'update_success' => 'Service updated successfully.',
            'deletion_failed' => 'Failed to delete the service. Please try again later.',
            'deletion_success' => 'Service deleted successfully.',
            'deletion_unauthorized' => 'You are not authorized to delete this service.',
        ],
        'credential' => [
            'creation_failed' => 'Failed to create the credential. Please try again later.',
            'creation_success' => 'Credential created successfully.',
            'update_failed' => 'Failed to update the credential. Please try again later.',
            'update_success' => 'Credential updated successfully.',
            'deletion_unauthorized' => 'You are not authorized to delete this credential.',
            'deletion_failed' => 'Failed to delete the credential. Please try again later.',
            'deletion_success' => 'Credential deleted successfully.',
            'approved' => [
                'title' => 'Credential Approved',
                'body' => 'Great news! Your credential :name has been verified and approved.',
            ],
            'tesda_approved' => [
                'title' => 'TESDA Certification Verified',
                'body' => 'Congratulations! Your TESDA certification :name has been officially verified and approved.',
            ],
            'rejected' => [
                'title' => 'Credential Rejected',
                'body' => 'We were unable to verify your credential :name. Please review the feedback or try uploading a valid document again.',
            ],
        ],
        'purchase' => [
            'corrupted' => 'The purchase record is corrupted or unreadable.',
            'invalid_price' => 'The product has an invalid price and cannot be purchased.',
            'not_allowed' => 'You are not allowed to purchase this product.',
            'success' => 'Product purchased successfully.',
        ],
        'topup' => [
            'minimum_amount' => 'The minimum top-up amount is :amount.',
            'maximum_amount' => 'The maximum top-up amount is :amount.',
            'amount_exceeds_product_price' => 'The top-up amount cannot exceed the product price.',
            'invalid_amount' => 'The top-up amount must be greater than zero.',
            'invalid_reference_number' => 'The reference number provided is invalid.',
            'success' => 'Credits topped up successfully.',
        ],
        'deduction' => [
            'invalid_amount' => 'The deduction amount must be greater than zero.',
            'insufficient_credits' => 'You do not have sufficient credits for this deduction.',
        ],
        'offer' => [
            'acceptance_failed' => 'Failed to accept the offer. It may have already been accepted by another expert.',
            'accepted_success' => 'Offer accepted successfully.',
            'accepted_message' => [
                'title' => 'Offer Accepted',
                'body' => ':expert_name has accepted the offer ":offer_name". Messaging is now open.',
            ],
            'direct_message' => [
                'title' => 'Direct Offer',
                'body' => ':client_name has sent a direct offer ":offer_name" to you.',
            ],
            'access_denied' => 'You are not authorized to access this offer.',
            'credential_required' => 'You must have at least one approved credential to accept offers. Please update your credentials.',
            'acceptance_fee' => 'Deducted :amount credits as offer acceptance fee.',
            'insufficient_credits' => 'You do not have sufficient credits to accept this offer. Please top-up your credits.',
            'not_found' => 'The requested offer was not found or is not available.',
            'complaint_submission_failed' => 'Failed to submit the complaint. Please try again later.',
            'complaint_submitted_success' => 'Complaint submitted successfully.',
        ],
        'chat' => [
            'expert_not_found' => 'Expert associated with the selected offer was not found.',
            'client_not_found' => 'Client associated with the selected offer was not found.',
            'session_not_found' => 'Chat session not found for the selected offer.',
        ],
        'feedback' => [
            'success' => 'Feedback submitted successfully.',
            'error' => 'Failed to submit feedback. Please try again later.',
            'duplicate' => 'You have already submitted feedback for this expert.',
            'limit_reached' => 'You have reached the maximum number of feedback submissions.',
        ],
        'phone' => [
            'success' => 'You have successfully updated your phone.',
        ],
    ],
    'client' => [
        'offer' => [
            'direct_offered_message' => [
                'title' => 'New Direct Offer',
                'body' => ':client_name has offered ":offer_name". Pending for acceptance.',
                'anonymous' => 'an anonymous client',
            ],
            'invalid_expert' => 'The specified expert for the direct offer is invalid or does not exist.',
        ],
    ],
    'offer' => [
        'success' => 'Offer created successfully.',
        'error' => 'Failed to create the offer. Please try again.',
        'update_failed' => 'Failed to update the offer. Please try again.',
        'update_success' => 'Updated the offer successfully.',
        'deletion_failed' => 'Failed to delete the offer. Please try again later.',
        'deletion_success' => 'Offer deleted successfully.',
    ],
    'log' => [
        'description' => [
            'created' => 'A new :model was created by :user.',
            'updated' => 'A :model was updated by :user.',
            'deleted' => 'A :model was deleted by :user.',
            'login' => ':user was logged in',
            'logout' => ':user was logged out',
            'login_failed' => 'Failed login attempt for :user',
            'auth_event' => ':user performed :event',
            'default' => ':action performed on :model by :user.',
        ],
    ],
];
