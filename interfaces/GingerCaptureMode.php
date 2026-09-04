<?php

/**
 * For payment methods that let the merchant choose how the transaction is captured (ex. Vipps/MobilePay)
 * Interface GingerCaptureMode
 */
interface GingerCaptureMode
{
    /**
     * The transaction is authorized only, the capture is triggered by the merchant
     */
    const GINGER_CAPTURE_MODE_MANUAL = 'manual';

    /**
     * The transaction is authorized and captured automatically by the gateway after the reservation period
     */
    const GINGER_CAPTURE_MODE_DELAYED = 'delayed';

    /**
     * Capture mode used when the merchant did not save the setting yet
     */
    const GINGER_DEFAULT_CAPTURE_MODE = self::GINGER_CAPTURE_MODE_MANUAL;

    /**
     * Reservation periods selectable for a delayed capture, as ISO 8601 durations.
     * The gateway rejects a delayed capture that reserves the amount for less than an hour
     * or for more than seven days, so the list stays inside that window.
     */
    const GINGER_DELAYED_CAPTURE_PERIODS = ['PT1H', 'PT6H', 'PT12H', 'P1D', 'P2D', 'P3D', 'P7D'];

    /**
     * Reservation period used when the merchant did not save the setting yet
     */
    const GINGER_DEFAULT_DELAYED_CAPTURE_PERIOD = 'P7D';

    /**
     * Capture modes selectable in the payment method settings
     */
    const GINGER_CAPTURE_MODES = [
        self::GINGER_CAPTURE_MODE_MANUAL,
        self::GINGER_CAPTURE_MODE_DELAYED,
    ];
}
