import 'dart:async';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/payment_model.dart';
import '../services/payment_service.dart';
import 'payment_success_screen.dart';

class PaymentProcessingScreen extends StatefulWidget {
  final PaymentData paymentData;

  const PaymentProcessingScreen({super.key, required this.paymentData});

  @override
  State<PaymentProcessingScreen> createState() =>
      _PaymentProcessingScreenState();
}

class _PaymentProcessingScreenState extends State<PaymentProcessingScreen> {
  final PaymentService _paymentService = PaymentService();
  Timer? _pollingTimer;
  bool _isPolling = false;
  String _statusMessage = 'Waiting for payment...';

  @override
  void initState() {
    super.initState();
    _startPolling();

    // Auto-open deeplink if available
    if (widget.paymentData.abapayDeeplink != null) {
      _openDeeplink();
    }
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  void _startPolling() {
    _isPolling = true;
    // Poll every 3 seconds
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) async {
      await _checkPaymentStatus();
    });
  }

  void _stopPolling() {
    _pollingTimer?.cancel();
    _isPolling = false;
  }

  Future<void> _checkPaymentStatus() async {
    try {
      final response = await _paymentService.checkPaymentStatus(
        widget.paymentData.tranId,
      );

      if (!mounted) return;

      if (response.success && response.data != null) {
        final status = response.data!.paymentStatus;

        if (status == 'paid') {
          _stopPolling();
          // Navigate to success screen
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (context) =>
                  PaymentSuccessScreen(paymentData: response.data!),
            ),
          );
        } else if (status == 'failed') {
          _stopPolling();
          setState(() {
            _statusMessage = 'Payment failed';
          });
          _showErrorDialog('Payment failed. Please try again.');
        } else if (status == 'expired') {
          _stopPolling();
          setState(() {
            _statusMessage = 'Payment expired';
          });
          _showErrorDialog('Payment expired. Please try again.');
        }
      }
    } catch (e) {
      // Continue polling even if there's an error
      debugPrint('Error checking payment status: $e');
    }
  }

  Future<void> _openDeeplink() async {
    final deeplink = widget.paymentData.abapayDeeplink;
    if (deeplink == null || deeplink.isEmpty) {
      debugPrint('No deeplink available');
      return;
    }

    try {
      debugPrint('Attempting to open deeplink: $deeplink');
      final uri = Uri.parse(deeplink);

      // Try to launch with external application mode
      final launched = await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      );

      if (!launched) {
        debugPrint('Failed to launch deeplink');
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'Cannot open ABA app. Please scan QR code instead.',
              ),
              duration: Duration(seconds: 3),
            ),
          );
        }
      } else {
        debugPrint('Deeplink launched successfully');
      }
    } catch (e) {
      debugPrint('Error opening deeplink: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}. Please scan QR code.'),
            duration: const Duration(seconds: 3),
          ),
        );
      }
    }
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Payment Error'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context); // Close dialog
              Navigator.pop(context); // Go back to payment screen
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Complete Payment'),
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(height: 20),

            // Payment amount display
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    const Text(
                      'Amount to Pay',
                      style: TextStyle(fontSize: 16, color: Colors.grey),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '\$${widget.paymentData.amount.toStringAsFixed(2)}',
                      style: const TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Transaction: ${widget.paymentData.tranId}',
                      style: const TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            // QR Code display
            if (widget.paymentData.qrString != null) ...[
              const Text(
                'Scan with ABA Mobile App',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withOpacity(0.2),
                      spreadRadius: 2,
                      blurRadius: 5,
                    ),
                  ],
                ),
                child: QrImageView(
                  data: widget.paymentData.qrString!,
                  version: QrVersions.auto,
                  size: 250.0,
                  backgroundColor: Colors.white,
                ),
              ),
              const SizedBox(height: 24),
            ],

            // Deeplink button
            if (widget.paymentData.abapayDeeplink != null) ...[
              const Text(
                'Or',
                style: TextStyle(fontSize: 16, color: Colors.grey),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _openDeeplink,
                icon: const Icon(Icons.open_in_new),
                label: const Text('Open in ABA App'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 16,
                  ),
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                ),
              ),
              const SizedBox(height: 24),
            ],

            // Status indicator
            Card(
              color: Colors.blue.shade50,
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    if (_isPolling)
                      const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _statusMessage,
                        style: const TextStyle(fontSize: 16),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            const Text(
              'Complete the payment in your ABA app.\nThis page will automatically update when payment is confirmed.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),

            const SizedBox(height: 16),

            TextButton(
              onPressed: () {
                _stopPolling();
                Navigator.pop(context);
              },
              child: const Text('Cancel Payment'),
            ),
          ],
        ),
      ),
    );
  }
}
