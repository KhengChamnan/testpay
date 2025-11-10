class PaymentRequest {
  final double amount;
  final String? currency;
  final String? paymentOption;
  final int? userId;
  final int? bookingId;
  final String? firstname;
  final String? lastname;
  final String? email;
  final String? phone;
  final String? returnDeeplink;
  final int? lifetime;

  PaymentRequest({
    required this.amount,
    this.currency = 'USD',
    this.paymentOption = 'abapay_khqr_deeplink',
    this.userId,
    this.bookingId,
    this.firstname,
    this.lastname,
    this.email,
    this.phone,
    this.returnDeeplink = 'myapp://payment',
    this.lifetime = 10,
  });

  Map<String, dynamic> toJson() {
    return {
      'amount': amount,
      'currency': currency,
      'payment_option': paymentOption,
      if (userId != null) 'user_id': userId,
      if (bookingId != null) 'booking_id': bookingId,
      if (firstname != null) 'firstname': firstname,
      if (lastname != null) 'lastname': lastname,
      if (email != null) 'email': email,
      if (phone != null) 'phone': phone,
      'return_deeplink': returnDeeplink,
      'lifetime': lifetime,
    };
  }
}

class PaymentResponse {
  final bool success;
  final String? message;
  final PaymentData? data;
  final dynamic errors;

  PaymentResponse({
    required this.success,
    this.message,
    this.data,
    this.errors,
  });

  factory PaymentResponse.fromJson(Map<String, dynamic> json) {
    return PaymentResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'] != null ? PaymentData.fromJson(json['data']) : null,
      errors: json['errors'],
    );
  }
}

class PaymentData {
  final String tranId;
  final double amount;
  final String currency;
  final String paymentStatus;
  final String? qrString;
  final String? abapayDeeplink;
  final String? paymentOption;
  final DateTime? paidAt;

  PaymentData({
    required this.tranId,
    required this.amount,
    required this.currency,
    required this.paymentStatus,
    this.qrString,
    this.abapayDeeplink,
    this.paymentOption,
    this.paidAt,
  });

  factory PaymentData.fromJson(Map<String, dynamic> json) {
    return PaymentData(
      tranId: json['tran_id'],
      amount: double.parse(json['amount'].toString()),
      currency: json['currency'] ?? 'USD',
      paymentStatus: json['payment_status'],
      qrString: json['qr_string'],
      abapayDeeplink: json['abapay_deeplink'],
      paymentOption: json['payment_option'],
      paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
    );
  }

  bool get isPending => paymentStatus == 'pending';
  bool get isPaid => paymentStatus == 'paid';
  bool get isFailed => paymentStatus == 'failed';
  bool get isExpired => paymentStatus == 'expired';
}

class PaymentStatusResponse {
  final bool success;
  final String? message;
  final PaymentData? data;

  PaymentStatusResponse({required this.success, this.message, this.data});

  factory PaymentStatusResponse.fromJson(Map<String, dynamic> json) {
    return PaymentStatusResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'] != null ? PaymentData.fromJson(json['data']) : null,
    );
  }
}
