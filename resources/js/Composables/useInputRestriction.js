/**
 * useInputRestriction.js
 * Composable for centralized input restrictions and formatting.
 */
export function useInputRestriction() {
    /**
     * Restrict input to numeric characters only (0-9).
     * Optionally allow decimals and positive only.
     */
    const restrictNumeric = (value, allowDecimal = true, allowNegative = false) => {
        if (value === null || value === undefined) return '';
        
        let val = value.toString();
        
        // Remove emojis and special characters first
        val = val.replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]|\u200D/g, '');
        
        if (!allowNegative) {
            val = val.replace(/-/g, '');
        }
        
        if (allowDecimal) {
            // Remove everything except digits, minus sign (if allowed), and decimal point
            const regex = allowNegative ? /[^\d.-]/g : /[^\d.]/g;
            val = val.replace(regex, '');
            
            // Handle multiple decimal points
            const parts = val.split('.');
            if (parts.length > 2) {
                val = parts[0] + '.' + parts.slice(1).join('');
            }
        } else {
            // Remove everything except digits and minus sign (if allowed)
            const regex = allowNegative ? /[^\d-]/g : /\D/g;
            val = val.replace(regex, '');
        }
        
        return val;
    };

    /**
     * Restrict input to alphanumeric characters only (a-zA-Z0-9).
     */
    const restrictAlphanumeric = (value) => {
        if (!value) return '';
        return value.toString().replace(/[^a-zA-Z0-9]/g, '');
    };

    /**
     * Validate email format.
     */
    const isValidEmail = (email) => {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    };

    /**
     * Format a date string to YYYY-MM-DD for input[type="date"] compatibility.
     */
    const formatDateForInput = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    };

    return {
        restrictNumeric,
        restrictAlphanumeric,
        isValidEmail,
        formatDateForInput
    };
}
