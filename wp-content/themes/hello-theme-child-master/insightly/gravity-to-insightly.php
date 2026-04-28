<?php
defined('ABSPATH') || exit;

/**
 * Build label => value map from the submitted entry safely.
 */
$output = '';

foreach ( $form['fields'] as $field ) {
    $has_inputs = is_array( $field->inputs ) && ! empty( $field->inputs );

    if ( $has_inputs ) {
        foreach ( $field->inputs as $input ) {
            $label    = isset($input['label']) && $input['label'] !== '' ? $input['label'] : $field->label;
            $input_id = (string) $input['id'];
            $value    = rgar( $entry, $input_id );
            if ( $value !== '' && $value !== null ) {
                $output .= $label . '!-@' . $value . '#@';
            }
        }
    } else {
        $label    = $field->label;
        $input_id = (string) $field->id;
        $value    = rgar( $entry, $input_id );
        if ( $value !== '' && $value !== null ) {
            $output .= $label . '!-@' . $value . '#@';
        }
    }
}

$map = [];
if ( $output !== '' ) {
    $lines = explode( '#@', rtrim( $output, '#@' ) );
    foreach ( $lines as $line ) {
        $parts = explode( '!-@', $line, 2 );
        if ( count( $parts ) === 2 ) {
            $map[ trim( $parts[0] ) ] = $parts[1];
        }
    }
}

/** Read API key (set in Settings → Insightly Settings) */
$api_key = get_option( 'insightly_api_key' );
if ( empty( $api_key ) ) {
    error_log('Insightly: API key missing. Aborting.');
    return;
}

/** Pull values for your CURRENT form labels */
$name_str   = $map['Name']             ?? '';
$email      = $map['Email']            ?? '';
$phone      = $map['Phone Number']     ?? ($map['Phone'] ?? '');
$zip        = $map['ZIP/Postal Code']  ?? ($map['Zip'] ?? '');
$company    = $map['Company']          ?? '';
$services   = $map['Choose Services']  ?? ($map['Services'] ?? '');
$purpose    = $map['Purpose']          ?? '';
$message    = $map['Message']          ?? '';

/** Split name to first/last */
$parts = array_values( array_filter( preg_split('/\s+/', trim($name_str)) ) );
$first = $parts[0] ?? 'Unknown';
$last  = trim( implode(' ', array_slice($parts, 1)) );
if ( $last === '' ) { $last = 'None'; }

/** Compose description including purpose/services */
$desc_lines = [];
if ($message !== '') $desc_lines[] = $message;
if ($purpose !== '') $desc_lines[] = 'Purpose: ' . $purpose;
if ($services !== '') $desc_lines[] = 'Services: ' . $services;
$lead_description = trim( implode("\n", $desc_lines) );

/** Optional context */
$http_uri   = home_url();
$source_url = $entry['source_url'] ?? '';

/** Build payload for Insightly v2.2 Leads */
$payload = [
    'FIRST_NAME'        => $first,
    'LAST_NAME'         => $last,
    'EMAIL_ADDRESS'     => $email,
    'PHONE_NUMBER'      => $phone,
    'ORGANISATION_NAME' => $company,          // shows as Company in Insightly
    'ADDRESS_POSTCODE'  => $zip,
    'LEAD_DESCRIPTION'  => $lead_description,
    'CUSTOMFIELDS'      => array_values(array_filter([
        ['CUSTOM_FIELD_ID' => 'LEAD_FIELD_3', 'FIELD_VALUE' => $source_url ?: ''],
        ['CUSTOM_FIELD_ID' => 'LEAD_FIELD_5', 'FIELD_VALUE' => $http_uri],
        // Keep Services in your legacy custom field if it exists:
        $services !== '' ? ['CUSTOM_FIELD_ID' => 'LEAD_FIELD_7', 'FIELD_VALUE' => $services] : null,
    ])),
];

/** Send to Insightly */
$ch = curl_init('https://api.insight.ly/v2.2/Leads');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER      => [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($api_key . ':'), // IMPORTANT
    ],
    CURLOPT_POST            => true,
    CURLOPT_POSTFIELDS      => wp_json_encode($payload),
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_TIMEOUT         => 30,
    CURLOPT_SSL_VERIFYPEER  => true, // recommended
]);

$response = curl_exec($ch);
if ($response === false) {
    error_log('Insightly cURL error: ' . curl_error($ch));
} else {
    error_log('Insightly response: ' . $response);
}
curl_close($ch);
