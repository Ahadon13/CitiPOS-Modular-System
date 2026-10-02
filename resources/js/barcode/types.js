/**
 * Barcode type recognition for "allowed barcode types".
 *
 * A scanner in keyboard or serial mode sends only the characters of a code,
 * not its symbology. The type is worked out two ways:
 *
 *  1. Exactly, from an AIM symbology identifier when the scanner is set to
 *     send one: "]" + symbology letter + modifier, e.g. "]E0" (EAN/UPC),
 *     "]C0" (Code 128), "]A0" (Code 39), "]Q1" (QR). The prefix is removed
 *     before the code is looked up.
 *  2. Otherwise from the content: EAN-13, EAN-8, UPC-A and UPC-E have a fixed
 *     length and a check digit, so they are recognised reliably. Code 128,
 *     Code 39 and QR cannot be told apart by content, so without an AIM ID
 *     they are one group: a code in that group is accepted if any of them is
 *     allowed.
 */

export const BARCODE_TYPES = {
    ean13: "EAN-13",
    ean8: "EAN-8",
    upca: "UPC-A",
    upce: "UPC-E",
    code128: "Code 128",
    code39: "Code 39",
    qr: "QR code",
    other: "Other types",
};

const RETAIL_TYPES = ["ean13", "ean8", "upca", "upce"];
const NON_RETAIL_TYPES = ["code128", "code39", "qr", "other"];

/** AIM symbology letters this app distinguishes; any other letter is "other". */
const AIM_SYMBOLOGIES = {
    E: "retail", // EAN / UPC family; refined by content
    C: "code128",
    A: "code39",
    Q: "qr",
};

/**
 * GS1 mod-10 check digit: weights 3,1,3,1... from the right, excluding the
 * check digit itself. Used by EAN-13, EAN-8 and UPC-A.
 */
export function hasValidCheckDigit(digits) {
    if (!/^\d+$/.test(digits) || digits.length < 2) return false;

    const body = digits.slice(0, -1);
    let sum = 0;

    for (let i = 0; i < body.length; i++) {
        const digit = Number(body[body.length - 1 - i]);
        sum += i % 2 === 0 ? digit * 3 : digit;
    }

    return (10 - (sum % 10)) % 10 === Number(digits[digits.length - 1]);
}

/**
 * UPC-E (8 digits: number system, 6 data digits, check digit) expanded to
 * its 12-digit UPC-A equivalent, whose check digit it shares.
 */
export function expandUpcE(code) {
    if (!/^[01]\d{7}$/.test(code)) return null;

    const ns = code[0];
    const d = code.slice(1, 7);
    const check = code[7];
    const last = d[5];
    let body;

    if (last === "0" || last === "1" || last === "2") {
        body = d[0] + d[1] + last + "0000" + d[2] + d[3] + d[4];
    } else if (last === "3") {
        body = d[0] + d[1] + d[2] + "00000" + d[3] + d[4];
    } else if (last === "4") {
        body = d[0] + d[1] + d[2] + d[3] + "00000" + d[4];
    } else {
        body = d[0] + d[1] + d[2] + d[3] + d[4] + "0000" + last;
    }

    return ns + body + check;
}

/**
 * Retail (EAN/UPC) types the content is valid for. Empty means it is not a
 * valid EAN/UPC code at all.
 */
export function retailTypesFor(code) {
    const types = [];

    if (/^\d{13}$/.test(code) && hasValidCheckDigit(code)) {
        types.push("ean13");
        // A UPC-A is an EAN-13 with a leading 0; scanners may send either form.
        if (code[0] === "0") types.push("upca");
    } else if (/^\d{12}$/.test(code) && hasValidCheckDigit(code)) {
        types.push("upca");
    } else if (/^\d{8}$/.test(code)) {
        if (hasValidCheckDigit(code)) types.push("ean8");

        const expanded = expandUpcE(code);
        if (expanded && hasValidCheckDigit(expanded)) types.push("upce");
    }

    return types;
}

/**
 * Work out what was scanned.
 *
 * @returns {{ code: string, aim: string|null, types: string[], exact: boolean }}
 *   code  - the barcode with any AIM identifier removed
 *   types - every type the scan could be (one when exact)
 */
export function classifyBarcode(raw) {
    const match = /^\]([A-Za-z])([0-9A-Za-z])/.exec(raw);
    const aim = match ? match[1] + match[2] : null;
    const code = match ? raw.slice(3) : raw;
    const retail = retailTypesFor(code);

    if (aim) {
        const symbology = AIM_SYMBOLOGIES[match[1]] ?? "other";

        if (symbology === "retail") {
            // "]E4" is EAN-8 by definition. Other modifiers are EAN-13, UPC-A
            // or UPC-E: the content says which, unless it is a form the content
            // rules do not cover (6-digit UPC-E, add-on digits) -- then any of the three.
            const types = match[2] === "4" ? ["ean8"] : retail.filter((t) => t !== "ean8");

            return { code, aim, types: types.length ? types : ["ean13", "upca", "upce"], exact: true };
        }

        return { code, aim, types: [symbology], exact: true };
    }

    if (retail.length) {
        return { code, aim: null, types: retail, exact: false };
    }

    return { code, aim: null, types: [...NON_RETAIL_TYPES], exact: false };
}

/**
 * A scan is allowed when any type it could be is allowed. No list (an older
 * scanner profile) means everything is allowed.
 */
export function isAllowedBarcode(result, allowedTypes) {
    if (!Array.isArray(allowedTypes)) return true;

    return result.types.some((type) => allowedTypes.includes(type));
}

/** Human label for what was scanned, e.g. "EAN-13" or "Code 128 / Code 39 / QR code". */
export function describeBarcode(result) {
    if (!result.exact && result.types.length === NON_RETAIL_TYPES.length) {
        return "Code 128, Code 39 or QR (needs AIM ID to tell apart)";
    }

    return result.types.map((type) => BARCODE_TYPES[type] ?? type).join(" / ");
}

export { RETAIL_TYPES, NON_RETAIL_TYPES };
