import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Renders a flag for a given ISO 3166-1 alpha-2 [countryCode].
///
/// For most countries this is just the regional-indicator emoji. Syria ("SY")
/// is special-cased to show the new independence flag (green / white / black
/// with three red four-pointed stars) instead of the old regime emoji.
class FlagDisplay extends StatelessWidget {
  final String countryCode;

  /// Font size used for emoji flags. The Syria widget is scaled to match.
  final double size;

  const FlagDisplay({
    super.key,
    required this.countryCode,
    this.size = 24,
  });

  @override
  Widget build(BuildContext context) {
    if (countryCode.toUpperCase() == 'SY') {
      // Aspect ratio of a standard flag is 3:2.
      return SizedBox(
        width: size * 1.5,
        height: size,
        child: const CustomPaint(painter: _SyriaFlagPainter()),
      );
    }

    // All other countries: use the regional-indicator emoji.
    final upper = countryCode.toUpperCase();
    const base = 0x1F1E6;
    final emoji = String.fromCharCodes([
      base + (upper.codeUnitAt(0) - 0x41),
      base + (upper.codeUnitAt(1) - 0x41),
    ]);
    return Text(emoji, style: TextStyle(fontSize: size));
  }
}

/// Paints the Syrian independence flag (adopted after 2011 revolution).
///
/// Layout (top → bottom):
///   • Green stripe  — #007A3D
///   • White stripe  — #FFFFFF  (3 red four-pointed stars centred)
///   • Black stripe  — #000000
///
/// The painter fills whatever [Size] it is given, so wrap it in a [SizedBox]
/// with the desired aspect ratio before using it.
class _SyriaFlagPainter extends CustomPainter {
  const _SyriaFlagPainter();

  static const _green = Color(0xFF007A3D);
  static const _white = Color(0xFFFFFFFF);
  static const _black = Color(0xFF000000);
  static const _red = Color(0xFFCE1126);

  @override
  void paint(Canvas canvas, Size size) {
    final stripeH = size.height / 3;
    final paint = Paint()
      ..style = PaintingStyle.fill;

    // Green stripe
    paint.color = _green;
    canvas.drawRect(Rect.fromLTWH(0, 0, size.width, stripeH), paint);

    // White stripe
    paint.color = _white;
    canvas.drawRect(
        Rect.fromLTWH(0, stripeH, size.width, stripeH), paint);

    // Black stripe
    paint.color = _black;
    canvas.drawRect(
        Rect.fromLTWH(0, stripeH * 2, size.width, stripeH), paint);

    // Three four-pointed red stars in the white stripe.
    // Stars are spaced evenly at 25%, 50%, 75% of the width.
    paint.color = _red;
    final starR = stripeH * 0.38; // outer radius of each star
    final centerY = stripeH * 1.5; // vertical centre of white stripe

    for (final xFrac in [0.25, 0.50, 0.75]) {
      _drawFourPointedStar(
        canvas,
        paint,
        center: Offset(size.width * xFrac, centerY),
        outerRadius: starR,
        innerRadius: starR * 0.4,
      );
    }
  }

  /// Draws a four-pointed star (cross shape with pointed tips).
  void _drawFourPointedStar(Canvas canvas,
      Paint paint, {
        required Offset center,
        required double outerRadius,
        required double innerRadius,
      }) {
    // A four-pointed star has 8 vertices alternating between outer and inner
    // radii, starting at the top (−π/2) and rotating by π/4 each step.
    final path = Path();
    const points = 4;
    const totalVertices = points * 2;

    for (var i = 0; i < totalVertices; i++) {
      final angle = (math.pi / points) * i - math.pi / 2;
      final r = i.isEven ? outerRadius : innerRadius;
      final x = center.dx + r * math.cos(angle);
      final y = center.dy + r * math.sin(angle);
      if (i == 0) {
        path.moveTo(x, y);
      } else {
        path.lineTo(x, y);
      }
    }
    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
