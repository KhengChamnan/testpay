import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/payment_model.dart';

class PaymentService {
  // Update this to your backend URL
  static const String baseUrl =
      'https://elegant-many-oyster.ngrok-free.app/api';

  /// Create a new payment request
  Future<PaymentResponse> createPayment(PaymentRequest request) async {
    try {
      final url = Uri.parse('$baseUrl/payment/create');

      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode(request.toJson()),
      );

      if (response.statusCode == 201 || response.statusCode == 200) {
        final jsonResponse = jsonDecode(response.body);
        return PaymentResponse.fromJson(jsonResponse);
      } else {
        final jsonResponse = jsonDecode(response.body);
        return PaymentResponse(
          success: false,
          message: jsonResponse['message'] ?? 'Failed to create payment',
          errors: jsonResponse['errors'],
        );
      }
    } catch (e) {
      return PaymentResponse(
        success: false,
        message: 'Network error: ${e.toString()}',
      );
    }
  }

  /// Check payment status by transaction ID
  Future<PaymentStatusResponse> checkPaymentStatus(String tranId) async {
    try {
      final url = Uri.parse('$baseUrl/payment/status?tran_id=$tranId');

      final response = await http.get(
        url,
        headers: {'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        final jsonResponse = jsonDecode(response.body);
        return PaymentStatusResponse.fromJson(jsonResponse);
      } else {
        final jsonResponse = jsonDecode(response.body);
        return PaymentStatusResponse(
          success: false,
          message: jsonResponse['message'] ?? 'Failed to check payment status',
        );
      }
    } catch (e) {
      return PaymentStatusResponse(
        success: false,
        message: 'Network error: ${e.toString()}',
      );
    }
  }
}
