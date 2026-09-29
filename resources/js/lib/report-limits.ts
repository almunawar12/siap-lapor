/**
 * Cermin App\Support\ReportPayload::MAX_LENGTHS. Dipakai untuk maxLength dan
 * penghitung karakter; server tetap sumber kebenaran validasi.
 */
export const MAX_LENGTHS = {
    report_number: 150,
    supervisor_name: 255,
    supervisor_position: 255,
    assignment_number: 150,
    supervisor_address: 2000,
    activity_name: 500,
    activity_form: 2000,
    activity_purpose: 5000,
    activity_target: 5000,
    activity_location: 2000,
    findings: 20000,
    signing_place: 255,
    signer_name: 255,
} as const;
