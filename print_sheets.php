<?php
// [MenuTitle]: พิมพ์กระดาษทั้งชุด
// [MenuIcon]: fa-solid fa-print
// [MenuOrder]: 2
require_once 'header.php';
require_once '../config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล $pdo

$user_id = $_SESSION['user_id'] ?? 1; // ชั่วคราวกรณีเทสระบบ

// ดึงรายการข้อสอบทั้งหมดของผู้ใช้นี้
$stmt = $pdo->prepare("SELECT id, exam_name FROM exams WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อนักเรียนทั้งหมด จัดกลุ่มตาม exam_id (ให้ JS สลับตามที่เลือกได้ทันทีไม่ต้องโหลดใหม่)
$stmt_students = $pdo->prepare("SELECT exam_id, id, student_no, student_name FROM students WHERE exam_id IN (SELECT id FROM exams WHERE user_id = ?) ORDER BY (student_no + 0), student_no");
$stmt_students->execute([$user_id]);
$students_raw = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
$students_by_exam = [];
foreach ($students_raw as $st) {
    $students_by_exam[$st['exam_id']][] = $st;
}

// เผื่อเปิดมาจากปุ่ม "พิมพ์กระดาษทั้งชุด" ในหน้าสร้างข้อสอบ จะได้เลือกข้อสอบให้อัตโนมัติ
$preselect_exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;
?>

<script>
//---------------------------------------------------------------------
//
// QR Code Generator for JavaScript
//
// Copyright (c) 2009 Kazuhiko Arase
//
// URL: http://www.d-project.com/
//
// Licensed under the MIT license:
//  http://www.opensource.org/licenses/mit-license.php
//
// The word 'QR Code' is registered trademark of
// DENSO WAVE INCORPORATED
//  http://www.denso-wave.com/qrcode/faqpatent-e.html
//
//---------------------------------------------------------------------

var qrcode = function() {

  //---------------------------------------------------------------------
  // qrcode
  //---------------------------------------------------------------------

  /**
   * qrcode
   * @param typeNumber 1 to 40
   * @param errorCorrectionLevel 'L','M','Q','H'
   */
  var qrcode = function(typeNumber, errorCorrectionLevel) {

    var PAD0 = 0xEC;
    var PAD1 = 0x11;

    var _typeNumber = typeNumber;
    var _errorCorrectionLevel = QRErrorCorrectionLevel[errorCorrectionLevel];
    var _modules = null;
    var _moduleCount = 0;
    var _dataCache = null;
    var _dataList = new Array();

    var _this = {};

    var makeImpl = function(test, maskPattern) {

      _moduleCount = _typeNumber * 4 + 17;
      _modules = function(moduleCount) {
        var modules = new Array(moduleCount);
        for (var row = 0; row < moduleCount; row += 1) {
          modules[row] = new Array(moduleCount);
          for (var col = 0; col < moduleCount; col += 1) {
            modules[row][col] = null;
          }
        }
        return modules;
      }(_moduleCount);

      setupPositionProbePattern(0, 0);
      setupPositionProbePattern(_moduleCount - 7, 0);
      setupPositionProbePattern(0, _moduleCount - 7);
      setupPositionAdjustPattern();
      setupTimingPattern();
      setupTypeInfo(test, maskPattern);

      if (_typeNumber >= 7) {
        setupTypeNumber(test);
      }

      if (_dataCache == null) {
        _dataCache = createData(_typeNumber, _errorCorrectionLevel, _dataList);
      }

      mapData(_dataCache, maskPattern);
    };

    var setupPositionProbePattern = function(row, col) {

      for (var r = -1; r <= 7; r += 1) {

        if (row + r <= -1 || _moduleCount <= row + r) continue;

        for (var c = -1; c <= 7; c += 1) {

          if (col + c <= -1 || _moduleCount <= col + c) continue;

          if ( (0 <= r && r <= 6 && (c == 0 || c == 6) )
              || (0 <= c && c <= 6 && (r == 0 || r == 6) )
              || (2 <= r && r <= 4 && 2 <= c && c <= 4) ) {
            _modules[row + r][col + c] = true;
          } else {
            _modules[row + r][col + c] = false;
          }
        }
      }
    };

    var getBestMaskPattern = function() {

      var minLostPoint = 0;
      var pattern = 0;

      for (var i = 0; i < 8; i += 1) {

        makeImpl(true, i);

        var lostPoint = QRUtil.getLostPoint(_this);

        if (i == 0 || minLostPoint > lostPoint) {
          minLostPoint = lostPoint;
          pattern = i;
        }
      }

      return pattern;
    };

    var setupTimingPattern = function() {

      for (var r = 8; r < _moduleCount - 8; r += 1) {
        if (_modules[r][6] != null) {
          continue;
        }
        _modules[r][6] = (r % 2 == 0);
      }

      for (var c = 8; c < _moduleCount - 8; c += 1) {
        if (_modules[6][c] != null) {
          continue;
        }
        _modules[6][c] = (c % 2 == 0);
      }
    };

    var setupPositionAdjustPattern = function() {

      var pos = QRUtil.getPatternPosition(_typeNumber);

      for (var i = 0; i < pos.length; i += 1) {

        for (var j = 0; j < pos.length; j += 1) {

          var row = pos[i];
          var col = pos[j];

          if (_modules[row][col] != null) {
            continue;
          }

          for (var r = -2; r <= 2; r += 1) {

            for (var c = -2; c <= 2; c += 1) {

              if (r == -2 || r == 2 || c == -2 || c == 2
                  || (r == 0 && c == 0) ) {
                _modules[row + r][col + c] = true;
              } else {
                _modules[row + r][col + c] = false;
              }
            }
          }
        }
      }
    };

    var setupTypeNumber = function(test) {

      var bits = QRUtil.getBCHTypeNumber(_typeNumber);

      for (var i = 0; i < 18; i += 1) {
        var mod = (!test && ( (bits >> i) & 1) == 1);
        _modules[Math.floor(i / 3)][i % 3 + _moduleCount - 8 - 3] = mod;
      }

      for (var i = 0; i < 18; i += 1) {
        var mod = (!test && ( (bits >> i) & 1) == 1);
        _modules[i % 3 + _moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
      }
    };

    var setupTypeInfo = function(test, maskPattern) {

      var data = (_errorCorrectionLevel << 3) | maskPattern;
      var bits = QRUtil.getBCHTypeInfo(data);

      // vertical
      for (var i = 0; i < 15; i += 1) {

        var mod = (!test && ( (bits >> i) & 1) == 1);

        if (i < 6) {
          _modules[i][8] = mod;
        } else if (i < 8) {
          _modules[i + 1][8] = mod;
        } else {
          _modules[_moduleCount - 15 + i][8] = mod;
        }
      }

      // horizontal
      for (var i = 0; i < 15; i += 1) {

        var mod = (!test && ( (bits >> i) & 1) == 1);

        if (i < 8) {
          _modules[8][_moduleCount - i - 1] = mod;
        } else if (i < 9) {
          _modules[8][15 - i - 1 + 1] = mod;
        } else {
          _modules[8][15 - i - 1] = mod;
        }
      }

      // fixed module
      _modules[_moduleCount - 8][8] = (!test);
    };

    var mapData = function(data, maskPattern) {

      var inc = -1;
      var row = _moduleCount - 1;
      var bitIndex = 7;
      var byteIndex = 0;
      var maskFunc = QRUtil.getMaskFunction(maskPattern);

      for (var col = _moduleCount - 1; col > 0; col -= 2) {

        if (col == 6) col -= 1;

        while (true) {

          for (var c = 0; c < 2; c += 1) {

            if (_modules[row][col - c] == null) {

              var dark = false;

              if (byteIndex < data.length) {
                dark = ( ( (data[byteIndex] >>> bitIndex) & 1) == 1);
              }

              var mask = maskFunc(row, col - c);

              if (mask) {
                dark = !dark;
              }

              _modules[row][col - c] = dark;
              bitIndex -= 1;

              if (bitIndex == -1) {
                byteIndex += 1;
                bitIndex = 7;
              }
            }
          }

          row += inc;

          if (row < 0 || _moduleCount <= row) {
            row -= inc;
            inc = -inc;
            break;
          }
        }
      }
    };

    var createBytes = function(buffer, rsBlocks) {

      var offset = 0;

      var maxDcCount = 0;
      var maxEcCount = 0;

      var dcdata = new Array(rsBlocks.length);
      var ecdata = new Array(rsBlocks.length);

      for (var r = 0; r < rsBlocks.length; r += 1) {

        var dcCount = rsBlocks[r].dataCount;
        var ecCount = rsBlocks[r].totalCount - dcCount;

        maxDcCount = Math.max(maxDcCount, dcCount);
        maxEcCount = Math.max(maxEcCount, ecCount);

        dcdata[r] = new Array(dcCount);

        for (var i = 0; i < dcdata[r].length; i += 1) {
          dcdata[r][i] = 0xff & buffer.getBuffer()[i + offset];
        }
        offset += dcCount;

        var rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
        var rawPoly = qrPolynomial(dcdata[r], rsPoly.getLength() - 1);

        var modPoly = rawPoly.mod(rsPoly);
        ecdata[r] = new Array(rsPoly.getLength() - 1);
        for (var i = 0; i < ecdata[r].length; i += 1) {
          var modIndex = i + modPoly.getLength() - ecdata[r].length;
          ecdata[r][i] = (modIndex >= 0)? modPoly.getAt(modIndex) : 0;
        }
      }

      var totalCodeCount = 0;
      for (var i = 0; i < rsBlocks.length; i += 1) {
        totalCodeCount += rsBlocks[i].totalCount;
      }

      var data = new Array(totalCodeCount);
      var index = 0;

      for (var i = 0; i < maxDcCount; i += 1) {
        for (var r = 0; r < rsBlocks.length; r += 1) {
          if (i < dcdata[r].length) {
            data[index] = dcdata[r][i];
            index += 1;
          }
        }
      }

      for (var i = 0; i < maxEcCount; i += 1) {
        for (var r = 0; r < rsBlocks.length; r += 1) {
          if (i < ecdata[r].length) {
            data[index] = ecdata[r][i];
            index += 1;
          }
        }
      }

      return data;
    };

    var createData = function(typeNumber, errorCorrectionLevel, dataList) {

      var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectionLevel);

      var buffer = qrBitBuffer();

      for (var i = 0; i < dataList.length; i += 1) {
        var data = dataList[i];
        buffer.put(data.getMode(), 4);
        buffer.put(data.getLength(), QRUtil.getLengthInBits(data.getMode(), typeNumber) );
        data.write(buffer);
      }

      // calc num max data.
      var totalDataCount = 0;
      for (var i = 0; i < rsBlocks.length; i += 1) {
        totalDataCount += rsBlocks[i].dataCount;
      }

      if (buffer.getLengthInBits() > totalDataCount * 8) {
        throw new Error('code length overflow. ('
          + buffer.getLengthInBits()
          + '>'
          + totalDataCount * 8
          + ')');
      }

      // end code
      if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) {
        buffer.put(0, 4);
      }

      // padding
      while (buffer.getLengthInBits() % 8 != 0) {
        buffer.putBit(false);
      }

      // padding
      while (true) {

        if (buffer.getLengthInBits() >= totalDataCount * 8) {
          break;
        }
        buffer.put(PAD0, 8);

        if (buffer.getLengthInBits() >= totalDataCount * 8) {
          break;
        }
        buffer.put(PAD1, 8);
      }

      return createBytes(buffer, rsBlocks);
    };

    _this.addData = function(data, mode) {

      mode = mode || 'Byte';

      var newData = null;

      switch(mode) {
      case 'Numeric' :
        newData = qrNumber(data);
        break;
      case 'Alphanumeric' :
        newData = qrAlphaNum(data);
        break;
      case 'Byte' :
        newData = qr8BitByte(data);
        break;
      case 'Kanji' :
        newData = qrKanji(data);
        break;
      default :
        throw 'mode:' + mode;
      }

      _dataList.push(newData);
      _dataCache = null;
    };

    _this.isDark = function(row, col) {
      if (row < 0 || _moduleCount <= row || col < 0 || _moduleCount <= col) {
        throw new Error(row + ',' + col);
      }
      return _modules[row][col];
    };

    _this.getModuleCount = function() {
      return _moduleCount;
    };

    _this.make = function() {
      if (!_typeNumber || _typeNumber < 1) {
        // typeNumber 0 = auto: ไล่ลองขนาด 1-40 จนกว่าข้อมูลจะพอดี (ไม่ overflow)
        var lastErr = null;
        for (var t = 1; t <= 40; t += 1) {
          try {
            _typeNumber = t;
            _dataCache = null;
            makeImpl(false, getBestMaskPattern() );
            return;
          } catch (e) {
            lastErr = e;
            _dataCache = null;
          }
        }
        throw lastErr || new Error('ไม่สามารถหาขนาด QR ที่พอดีกับข้อมูลได้ (ข้อมูลยาวเกินไป)');
      }
      makeImpl(false, getBestMaskPattern() );
    };

    _this.createTableTag = function(cellSize, margin) {

      cellSize = cellSize || 2;
      margin = (typeof margin == 'undefined')? cellSize * 4 : margin;

      var qrHtml = '';

      qrHtml += '<table style="';
      qrHtml += ' border-width: 0px; border-style: none;';
      qrHtml += ' border-collapse: collapse;';
      qrHtml += ' padding: 0px; margin: ' + margin + 'px;';
      qrHtml += '">';
      qrHtml += '<tbody>';

      for (var r = 0; r < _this.getModuleCount(); r += 1) {

        qrHtml += '<tr>';

        for (var c = 0; c < _this.getModuleCount(); c += 1) {
          qrHtml += '<td style="';
          qrHtml += ' border-width: 0px; border-style: none;';
          qrHtml += ' border-collapse: collapse;';
          qrHtml += ' padding: 0px; margin: 0px;';
          qrHtml += ' width: ' + cellSize + 'px;';
          qrHtml += ' height: ' + cellSize + 'px;';
          qrHtml += ' background-color: ';
          qrHtml += _this.isDark(r, c)? '#000000' : '#ffffff';
          qrHtml += ';';
          qrHtml += '"/>';
        }

        qrHtml += '</tr>';
      }

      qrHtml += '</tbody>';
      qrHtml += '</table>';

      return qrHtml;
    };

    _this.createSvgTag = function(cellSize, margin) {

      cellSize = cellSize || 2;
      margin = (typeof margin == 'undefined')? cellSize * 4 : margin;
      var size = _this.getModuleCount() * cellSize + margin * 2;
      var c, mc, r, mr, qrSvg='', rect;

      rect = 'l' + cellSize + ',0 0,' + cellSize +
        ' -' + cellSize + ',0 0,-' + cellSize + 'z ';

      qrSvg += '<svg';
      qrSvg += ' width="' + size + 'px"';
      qrSvg += ' height="' + size + 'px"';
      qrSvg += ' xmlns="http://www.w3.org/2000/svg"';
      qrSvg += '>';
      qrSvg += '<path d="';

      for (r = 0; r < _this.getModuleCount(); r += 1) {
        mr = r * cellSize + margin;
        for (c = 0; c < _this.getModuleCount(); c += 1) {
          if (_this.isDark(r, c) ) {
            mc = c*cellSize+margin;
            qrSvg += 'M' + mc + ',' + mr + rect;
          }
        }
      }

      qrSvg += '" stroke="transparent" fill="black"/>';
      qrSvg += '</svg>';

      return qrSvg;
    };

    _this.createImgTag = function(cellSize, margin) {

      cellSize = cellSize || 2;
      margin = (typeof margin == 'undefined')? cellSize * 4 : margin;

      var size = _this.getModuleCount() * cellSize + margin * 2;
      var min = margin;
      var max = size - margin;

      return createImgTag(size, size, function(x, y) {
        if (min <= x && x < max && min <= y && y < max) {
          var c = Math.floor( (x - min) / cellSize);
          var r = Math.floor( (y - min) / cellSize);
          return _this.isDark(r, c)? 0 : 1;
        } else {
          return 1;
        }
      } );
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // qrcode.stringToBytes
  //---------------------------------------------------------------------

  qrcode.stringToBytes = function(s) {
    var bytes = new Array();
    for (var i = 0; i < s.length; i += 1) {
      var c = s.charCodeAt(i);
      bytes.push(c & 0xff);
    }
    return bytes;
  };

  //---------------------------------------------------------------------
  // qrcode.createStringToBytes
  //---------------------------------------------------------------------

  /**
   * @param unicodeData base64 string of byte array.
   * [16bit Unicode],[16bit Bytes], ...
   * @param numChars
   */
  qrcode.createStringToBytes = function(unicodeData, numChars) {

    // create conversion map.

    var unicodeMap = function() {

      var bin = base64DecodeInputStream(unicodeData);
      var read = function() {
        var b = bin.read();
        if (b == -1) throw new Error();
        return b;
      };

      var count = 0;
      var unicodeMap = {};
      while (true) {
        var b0 = bin.read();
        if (b0 == -1) break;
        var b1 = read();
        var b2 = read();
        var b3 = read();
        var k = String.fromCharCode( (b0 << 8) | b1);
        var v = (b2 << 8) | b3;
        unicodeMap[k] = v;
        count += 1;
      }
      if (count != numChars) {
        throw new Error(count + ' != ' + numChars);
      }

      return unicodeMap;
    }();

    var unknownChar = '?'.charCodeAt(0);

    return function(s) {
      var bytes = new Array();
      for (var i = 0; i < s.length; i += 1) {
        var c = s.charCodeAt(i);
        if (c < 128) {
          bytes.push(c);
        } else {
          var b = unicodeMap[s.charAt(i)];
          if (typeof b == 'number') {
            if ( (b & 0xff) == b) {
              // 1byte
              bytes.push(b);
            } else {
              // 2bytes
              bytes.push(b >>> 8);
              bytes.push(b & 0xff);
            }
          } else {
            bytes.push(unknownChar);
          }
        }
      }
      return bytes;
    };
  };

  //---------------------------------------------------------------------
  // QRMode
  //---------------------------------------------------------------------

  var QRMode = {
    MODE_NUMBER :    1 << 0,
    MODE_ALPHA_NUM : 1 << 1,
    MODE_8BIT_BYTE : 1 << 2,
    MODE_KANJI :     1 << 3
  };

  //---------------------------------------------------------------------
  // QRErrorCorrectionLevel
  //---------------------------------------------------------------------

  var QRErrorCorrectionLevel = {
    L : 1,
    M : 0,
    Q : 3,
    H : 2
  };

  //---------------------------------------------------------------------
  // QRMaskPattern
  //---------------------------------------------------------------------

  var QRMaskPattern = {
    PATTERN000 : 0,
    PATTERN001 : 1,
    PATTERN010 : 2,
    PATTERN011 : 3,
    PATTERN100 : 4,
    PATTERN101 : 5,
    PATTERN110 : 6,
    PATTERN111 : 7
  };

  //---------------------------------------------------------------------
  // QRUtil
  //---------------------------------------------------------------------

  var QRUtil = function() {

    var PATTERN_POSITION_TABLE = [
      [],
      [6, 18],
      [6, 22],
      [6, 26],
      [6, 30],
      [6, 34],
      [6, 22, 38],
      [6, 24, 42],
      [6, 26, 46],
      [6, 28, 50],
      [6, 30, 54],
      [6, 32, 58],
      [6, 34, 62],
      [6, 26, 46, 66],
      [6, 26, 48, 70],
      [6, 26, 50, 74],
      [6, 30, 54, 78],
      [6, 30, 56, 82],
      [6, 30, 58, 86],
      [6, 34, 62, 90],
      [6, 28, 50, 72, 94],
      [6, 26, 50, 74, 98],
      [6, 30, 54, 78, 102],
      [6, 28, 54, 80, 106],
      [6, 32, 58, 84, 110],
      [6, 30, 58, 86, 114],
      [6, 34, 62, 90, 118],
      [6, 26, 50, 74, 98, 122],
      [6, 30, 54, 78, 102, 126],
      [6, 26, 52, 78, 104, 130],
      [6, 30, 56, 82, 108, 134],
      [6, 34, 60, 86, 112, 138],
      [6, 30, 58, 86, 114, 142],
      [6, 34, 62, 90, 118, 146],
      [6, 30, 54, 78, 102, 126, 150],
      [6, 24, 50, 76, 102, 128, 154],
      [6, 28, 54, 80, 106, 132, 158],
      [6, 32, 58, 84, 110, 136, 162],
      [6, 26, 54, 82, 110, 138, 166],
      [6, 30, 58, 86, 114, 142, 170]
    ];
    var G15 = (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0);
    var G18 = (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0);
    var G15_MASK = (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1);

    var _this = {};

    var getBCHDigit = function(data) {
      var digit = 0;
      while (data != 0) {
        digit += 1;
        data >>>= 1;
      }
      return digit;
    };

    _this.getBCHTypeInfo = function(data) {
      var d = data << 10;
      while (getBCHDigit(d) - getBCHDigit(G15) >= 0) {
        d ^= (G15 << (getBCHDigit(d) - getBCHDigit(G15) ) );
      }
      return ( (data << 10) | d) ^ G15_MASK;
    };

    _this.getBCHTypeNumber = function(data) {
      var d = data << 12;
      while (getBCHDigit(d) - getBCHDigit(G18) >= 0) {
        d ^= (G18 << (getBCHDigit(d) - getBCHDigit(G18) ) );
      }
      return (data << 12) | d;
    };

    _this.getPatternPosition = function(typeNumber) {
      return PATTERN_POSITION_TABLE[typeNumber - 1];
    };

    _this.getMaskFunction = function(maskPattern) {

      switch (maskPattern) {

      case QRMaskPattern.PATTERN000 :
        return function(i, j) { return (i + j) % 2 == 0; };
      case QRMaskPattern.PATTERN001 :
        return function(i, j) { return i % 2 == 0; };
      case QRMaskPattern.PATTERN010 :
        return function(i, j) { return j % 3 == 0; };
      case QRMaskPattern.PATTERN011 :
        return function(i, j) { return (i + j) % 3 == 0; };
      case QRMaskPattern.PATTERN100 :
        return function(i, j) { return (Math.floor(i / 2) + Math.floor(j / 3) ) % 2 == 0; };
      case QRMaskPattern.PATTERN101 :
        return function(i, j) { return (i * j) % 2 + (i * j) % 3 == 0; };
      case QRMaskPattern.PATTERN110 :
        return function(i, j) { return ( (i * j) % 2 + (i * j) % 3) % 2 == 0; };
      case QRMaskPattern.PATTERN111 :
        return function(i, j) { return ( (i * j) % 3 + (i + j) % 2) % 2 == 0; };

      default :
        throw new Error('bad maskPattern:' + maskPattern);
      }
    };

    _this.getErrorCorrectPolynomial = function(errorCorrectLength) {
      var a = qrPolynomial([1], 0);
      for (var i = 0; i < errorCorrectLength; i += 1) {
        a = a.multiply(qrPolynomial([1, QRMath.gexp(i)], 0) );
      }
      return a;
    };

    _this.getLengthInBits = function(mode, type) {

      if (1 <= type && type < 10) {

        // 1 - 9

        switch(mode) {
        case QRMode.MODE_NUMBER    : return 10;
        case QRMode.MODE_ALPHA_NUM : return 9;
        case QRMode.MODE_8BIT_BYTE : return 8;
        case QRMode.MODE_KANJI     : return 8;
        default :
          throw new Error('mode:' + mode);
        }

      } else if (type < 27) {

        // 10 - 26

        switch(mode) {
        case QRMode.MODE_NUMBER    : return 12;
        case QRMode.MODE_ALPHA_NUM : return 11;
        case QRMode.MODE_8BIT_BYTE : return 16;
        case QRMode.MODE_KANJI     : return 10;
        default :
          throw new Error('mode:' + mode);
        }

      } else if (type < 41) {

        // 27 - 40

        switch(mode) {
        case QRMode.MODE_NUMBER    : return 14;
        case QRMode.MODE_ALPHA_NUM : return 13;
        case QRMode.MODE_8BIT_BYTE : return 16;
        case QRMode.MODE_KANJI     : return 12;
        default :
          throw new Error('mode:' + mode);
        }

      } else {
        throw new Error('type:' + type);
      }
    };

    _this.getLostPoint = function(qrcode) {

      var moduleCount = qrcode.getModuleCount();

      var lostPoint = 0;

      // LEVEL1

      for (var row = 0; row < moduleCount; row += 1) {
        for (var col = 0; col < moduleCount; col += 1) {

          var sameCount = 0;
          var dark = qrcode.isDark(row, col);

          for (var r = -1; r <= 1; r += 1) {

            if (row + r < 0 || moduleCount <= row + r) {
              continue;
            }

            for (var c = -1; c <= 1; c += 1) {

              if (col + c < 0 || moduleCount <= col + c) {
                continue;
              }

              if (r == 0 && c == 0) {
                continue;
              }

              if (dark == qrcode.isDark(row + r, col + c) ) {
                sameCount += 1;
              }
            }
          }

          if (sameCount > 5) {
            lostPoint += (3 + sameCount - 5);
          }
        }
      };

      // LEVEL2

      for (var row = 0; row < moduleCount - 1; row += 1) {
        for (var col = 0; col < moduleCount - 1; col += 1) {
          var count = 0;
          if (qrcode.isDark(row, col) ) count += 1;
          if (qrcode.isDark(row + 1, col) ) count += 1;
          if (qrcode.isDark(row, col + 1) ) count += 1;
          if (qrcode.isDark(row + 1, col + 1) ) count += 1;
          if (count == 0 || count == 4) {
            lostPoint += 3;
          }
        }
      }

      // LEVEL3

      for (var row = 0; row < moduleCount; row += 1) {
        for (var col = 0; col < moduleCount - 6; col += 1) {
          if (qrcode.isDark(row, col)
              && !qrcode.isDark(row, col + 1)
              &&  qrcode.isDark(row, col + 2)
              &&  qrcode.isDark(row, col + 3)
              &&  qrcode.isDark(row, col + 4)
              && !qrcode.isDark(row, col + 5)
              &&  qrcode.isDark(row, col + 6) ) {
            lostPoint += 40;
          }
        }
      }

      for (var col = 0; col < moduleCount; col += 1) {
        for (var row = 0; row < moduleCount - 6; row += 1) {
          if (qrcode.isDark(row, col)
              && !qrcode.isDark(row + 1, col)
              &&  qrcode.isDark(row + 2, col)
              &&  qrcode.isDark(row + 3, col)
              &&  qrcode.isDark(row + 4, col)
              && !qrcode.isDark(row + 5, col)
              &&  qrcode.isDark(row + 6, col) ) {
            lostPoint += 40;
          }
        }
      }

      // LEVEL4

      var darkCount = 0;

      for (var col = 0; col < moduleCount; col += 1) {
        for (var row = 0; row < moduleCount; row += 1) {
          if (qrcode.isDark(row, col) ) {
            darkCount += 1;
          }
        }
      }

      var ratio = Math.abs(100 * darkCount / moduleCount / moduleCount - 50) / 5;
      lostPoint += ratio * 10;

      return lostPoint;
    };

    return _this;
  }();

  //---------------------------------------------------------------------
  // QRMath
  //---------------------------------------------------------------------

  var QRMath = function() {

    var EXP_TABLE = new Array(256);
    var LOG_TABLE = new Array(256);

    // initialize tables
    for (var i = 0; i < 8; i += 1) {
      EXP_TABLE[i] = 1 << i;
    }
    for (var i = 8; i < 256; i += 1) {
      EXP_TABLE[i] = EXP_TABLE[i - 4]
        ^ EXP_TABLE[i - 5]
        ^ EXP_TABLE[i - 6]
        ^ EXP_TABLE[i - 8];
    }
    for (var i = 0; i < 255; i += 1) {
      LOG_TABLE[EXP_TABLE[i] ] = i;
    }

    var _this = {};

    _this.glog = function(n) {

      if (n < 1) {
        throw new Error('glog(' + n + ')');
      }

      return LOG_TABLE[n];
    };

    _this.gexp = function(n) {

      while (n < 0) {
        n += 255;
      }

      while (n >= 256) {
        n -= 255;
      }

      return EXP_TABLE[n];
    };

    return _this;
  }();

  //---------------------------------------------------------------------
  // qrPolynomial
  //---------------------------------------------------------------------

  function qrPolynomial(num, shift) {

    if (typeof num.length == 'undefined') {
      throw new Error(num.length + '/' + shift);
    }

    var _num = function() {
      var offset = 0;
      while (offset < num.length && num[offset] == 0) {
        offset += 1;
      }
      var _num = new Array(num.length - offset + shift);
      for (var i = 0; i < num.length - offset; i += 1) {
        _num[i] = num[i + offset];
      }
      return _num;
    }();

    var _this = {};

    _this.getAt = function(index) {
      return _num[index];
    };

    _this.getLength = function() {
      return _num.length;
    };

    _this.multiply = function(e) {

      var num = new Array(_this.getLength() + e.getLength() - 1);

      for (var i = 0; i < _this.getLength(); i += 1) {
        for (var j = 0; j < e.getLength(); j += 1) {
          num[i + j] ^= QRMath.gexp(QRMath.glog(_this.getAt(i) ) + QRMath.glog(e.getAt(j) ) );
        }
      }

      return qrPolynomial(num, 0);
    };

    _this.mod = function(e) {

      if (_this.getLength() - e.getLength() < 0) {
        return _this;
      }

      var ratio = QRMath.glog(_this.getAt(0) ) - QRMath.glog(e.getAt(0) );

      var num = new Array(_this.getLength() );
      for (var i = 0; i < _this.getLength(); i += 1) {
        num[i] = _this.getAt(i);
      }

      for (var i = 0; i < e.getLength(); i += 1) {
        num[i] ^= QRMath.gexp(QRMath.glog(e.getAt(i) ) + ratio);
      }

      // recursive call
      return qrPolynomial(num, 0).mod(e);
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // QRRSBlock
  //---------------------------------------------------------------------

  var QRRSBlock = function() {

    var RS_BLOCK_TABLE = [

      // L
      // M
      // Q
      // H

      // 1
      [1, 26, 19],
      [1, 26, 16],
      [1, 26, 13],
      [1, 26, 9],

      // 2
      [1, 44, 34],
      [1, 44, 28],
      [1, 44, 22],
      [1, 44, 16],

      // 3
      [1, 70, 55],
      [1, 70, 44],
      [2, 35, 17],
      [2, 35, 13],

      // 4
      [1, 100, 80],
      [2, 50, 32],
      [2, 50, 24],
      [4, 25, 9],

      // 5
      [1, 134, 108],
      [2, 67, 43],
      [2, 33, 15, 2, 34, 16],
      [2, 33, 11, 2, 34, 12],

      // 6
      [2, 86, 68],
      [4, 43, 27],
      [4, 43, 19],
      [4, 43, 15],

      // 7
      [2, 98, 78],
      [4, 49, 31],
      [2, 32, 14, 4, 33, 15],
      [4, 39, 13, 1, 40, 14],

      // 8
      [2, 121, 97],
      [2, 60, 38, 2, 61, 39],
      [4, 40, 18, 2, 41, 19],
      [4, 40, 14, 2, 41, 15],

      // 9
      [2, 146, 116],
      [3, 58, 36, 2, 59, 37],
      [4, 36, 16, 4, 37, 17],
      [4, 36, 12, 4, 37, 13],

      // 10
      [2, 86, 68, 2, 87, 69],
      [4, 69, 43, 1, 70, 44],
      [6, 43, 19, 2, 44, 20],
      [6, 43, 15, 2, 44, 16],

      // 11
      [4, 101, 81],
      [1, 80, 50, 4, 81, 51],
      [4, 50, 22, 4, 51, 23],
      [3, 36, 12, 8, 37, 13],

      // 12
      [2, 116, 92, 2, 117, 93],
      [6, 58, 36, 2, 59, 37],
      [4, 46, 20, 6, 47, 21],
      [7, 42, 14, 4, 43, 15],

      // 13
      [4, 133, 107],
      [8, 59, 37, 1, 60, 38],
      [8, 44, 20, 4, 45, 21],
      [12, 33, 11, 4, 34, 12],

      // 14
      [3, 145, 115, 1, 146, 116],
      [4, 64, 40, 5, 65, 41],
      [11, 36, 16, 5, 37, 17],
      [11, 36, 12, 5, 37, 13],

      // 15
      [5, 109, 87, 1, 110, 88],
      [5, 65, 41, 5, 66, 42],
      [5, 54, 24, 7, 55, 25],
      [11, 36, 12, 7, 37, 13],

      // 16
      [5, 122, 98, 1, 123, 99],
      [7, 73, 45, 3, 74, 46],
      [15, 43, 19, 2, 44, 20],
      [3, 45, 15, 13, 46, 16],

      // 17
      [1, 135, 107, 5, 136, 108],
      [10, 74, 46, 1, 75, 47],
      [1, 50, 22, 15, 51, 23],
      [2, 42, 14, 17, 43, 15],

      // 18
      [5, 150, 120, 1, 151, 121],
      [9, 69, 43, 4, 70, 44],
      [17, 50, 22, 1, 51, 23],
      [2, 42, 14, 19, 43, 15],

      // 19
      [3, 141, 113, 4, 142, 114],
      [3, 70, 44, 11, 71, 45],
      [17, 47, 21, 4, 48, 22],
      [9, 39, 13, 16, 40, 14],

      // 20
      [3, 135, 107, 5, 136, 108],
      [3, 67, 41, 13, 68, 42],
      [15, 54, 24, 5, 55, 25],
      [15, 43, 15, 10, 44, 16],

      // 21
      [4, 144, 116, 4, 145, 117],
      [17, 68, 42],
      [17, 50, 22, 6, 51, 23],
      [19, 46, 16, 6, 47, 17],

      // 22
      [2, 139, 111, 7, 140, 112],
      [17, 74, 46],
      [7, 54, 24, 16, 55, 25],
      [34, 37, 13],

      // 23
      [4, 151, 121, 5, 152, 122],
      [4, 75, 47, 14, 76, 48],
      [11, 54, 24, 14, 55, 25],
      [16, 45, 15, 14, 46, 16],

      // 24
      [6, 147, 117, 4, 148, 118],
      [6, 73, 45, 14, 74, 46],
      [11, 54, 24, 16, 55, 25],
      [30, 46, 16, 2, 47, 17],

      // 25
      [8, 132, 106, 4, 133, 107],
      [8, 75, 47, 13, 76, 48],
      [7, 54, 24, 22, 55, 25],
      [22, 45, 15, 13, 46, 16],

      // 26
      [10, 142, 114, 2, 143, 115],
      [19, 74, 46, 4, 75, 47],
      [28, 50, 22, 6, 51, 23],
      [33, 46, 16, 4, 47, 17],

      // 27
      [8, 152, 122, 4, 153, 123],
      [22, 73, 45, 3, 74, 46],
      [8, 53, 23, 26, 54, 24],
      [12, 45, 15, 28, 46, 16],

      // 28
      [3, 147, 117, 10, 148, 118],
      [3, 73, 45, 23, 74, 46],
      [4, 54, 24, 31, 55, 25],
      [11, 45, 15, 31, 46, 16],

      // 29
      [7, 146, 116, 7, 147, 117],
      [21, 73, 45, 7, 74, 46],
      [1, 53, 23, 37, 54, 24],
      [19, 45, 15, 26, 46, 16],

      // 30
      [5, 145, 115, 10, 146, 116],
      [19, 75, 47, 10, 76, 48],
      [15, 54, 24, 25, 55, 25],
      [23, 45, 15, 25, 46, 16],

      // 31
      [13, 145, 115, 3, 146, 116],
      [2, 74, 46, 29, 75, 47],
      [42, 54, 24, 1, 55, 25],
      [23, 45, 15, 28, 46, 16],

      // 32
      [17, 145, 115],
      [10, 74, 46, 23, 75, 47],
      [10, 54, 24, 35, 55, 25],
      [19, 45, 15, 35, 46, 16],

      // 33
      [17, 145, 115, 1, 146, 116],
      [14, 74, 46, 21, 75, 47],
      [29, 54, 24, 19, 55, 25],
      [11, 45, 15, 46, 46, 16],

      // 34
      [13, 145, 115, 6, 146, 116],
      [14, 74, 46, 23, 75, 47],
      [44, 54, 24, 7, 55, 25],
      [59, 46, 16, 1, 47, 17],

      // 35
      [12, 151, 121, 7, 152, 122],
      [12, 75, 47, 26, 76, 48],
      [39, 54, 24, 14, 55, 25],
      [22, 45, 15, 41, 46, 16],

      // 36
      [6, 151, 121, 14, 152, 122],
      [6, 75, 47, 34, 76, 48],
      [46, 54, 24, 10, 55, 25],
      [2, 45, 15, 64, 46, 16],

      // 37
      [17, 152, 122, 4, 153, 123],
      [29, 74, 46, 14, 75, 47],
      [49, 54, 24, 10, 55, 25],
      [24, 45, 15, 46, 46, 16],

      // 38
      [4, 152, 122, 18, 153, 123],
      [13, 74, 46, 32, 75, 47],
      [48, 54, 24, 14, 55, 25],
      [42, 45, 15, 32, 46, 16],

      // 39
      [20, 147, 117, 4, 148, 118],
      [40, 75, 47, 7, 76, 48],
      [43, 54, 24, 22, 55, 25],
      [10, 45, 15, 67, 46, 16],

      // 40
      [19, 148, 118, 6, 149, 119],
      [18, 75, 47, 31, 76, 48],
      [34, 54, 24, 34, 55, 25],
      [20, 45, 15, 61, 46, 16]
    ];

    var qrRSBlock = function(totalCount, dataCount) {
      var _this = {};
      _this.totalCount = totalCount;
      _this.dataCount = dataCount;
      return _this;
    };

    var _this = {};

    var getRsBlockTable = function(typeNumber, errorCorrectionLevel) {

      switch(errorCorrectionLevel) {
      case QRErrorCorrectionLevel.L :
        return RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
      case QRErrorCorrectionLevel.M :
        return RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
      case QRErrorCorrectionLevel.Q :
        return RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
      case QRErrorCorrectionLevel.H :
        return RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
      default :
        return undefined;
      }
    };

    _this.getRSBlocks = function(typeNumber, errorCorrectionLevel) {

      var rsBlock = getRsBlockTable(typeNumber, errorCorrectionLevel);

      if (typeof rsBlock == 'undefined') {
        throw new Error('bad rs block @ typeNumber:' + typeNumber +
            '/errorCorrectionLevel:' + errorCorrectionLevel);
      }

      var length = rsBlock.length / 3;

      var list = new Array();

      for (var i = 0; i < length; i += 1) {

        var count = rsBlock[i * 3 + 0];
        var totalCount = rsBlock[i * 3 + 1];
        var dataCount = rsBlock[i * 3 + 2];

        for (var j = 0; j < count; j += 1) {
          list.push(qrRSBlock(totalCount, dataCount) );
        }
      }

      return list;
    };

    return _this;
  }();

  //---------------------------------------------------------------------
  // qrBitBuffer
  //---------------------------------------------------------------------

  var qrBitBuffer = function() {

    var _buffer = new Array();
    var _length = 0;

    var _this = {};

    _this.getBuffer = function() {
      return _buffer;
    };

    _this.getAt = function(index) {
      var bufIndex = Math.floor(index / 8);
      return ( (_buffer[bufIndex] >>> (7 - index % 8) ) & 1) == 1;
    };

    _this.put = function(num, length) {
      for (var i = 0; i < length; i += 1) {
        _this.putBit( ( (num >>> (length - i - 1) ) & 1) == 1);
      }
    };

    _this.getLengthInBits = function() {
      return _length;
    };

    _this.putBit = function(bit) {

      var bufIndex = Math.floor(_length / 8);
      if (_buffer.length <= bufIndex) {
        _buffer.push(0);
      }

      if (bit) {
        _buffer[bufIndex] |= (0x80 >>> (_length % 8) );
      }

      _length += 1;
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // qrNumber
  //---------------------------------------------------------------------

  var qrNumber = function(data) {

    var _mode = QRMode.MODE_NUMBER;
    var _data = data;

    var _this = {};

    _this.getMode = function() {
      return _mode;
    };

    _this.getLength = function(buffer) {
      return _data.length;
    };

    _this.write = function(buffer) {

      var data = _data;

      var i = 0;

      while (i + 2 < data.length) {
        buffer.put(strToNum(data.substring(i, i + 3) ), 10);
        i += 3;
      }

      if (i < data.length) {
        if (data.length - i == 1) {
          buffer.put(strToNum(data.substring(i, i + 1) ), 4);
        } else if (data.length - i == 2) {
          buffer.put(strToNum(data.substring(i, i + 2) ), 7);
        }
      }
    };

    var strToNum = function(s) {
      var num = 0;
      for (var i = 0; i < s.length; i += 1) {
        num = num * 10 + chatToNum(s.charAt(i) );
      }
      return num;
    };

    var chatToNum = function(c) {
      if ('0' <= c && c <= '9') {
        return c.charCodeAt(0) - '0'.charCodeAt(0);
      }
      throw 'illegal char :' + c;
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // qrAlphaNum
  //---------------------------------------------------------------------

  var qrAlphaNum = function(data) {

    var _mode = QRMode.MODE_ALPHA_NUM;
    var _data = data;

    var _this = {};

    _this.getMode = function() {
      return _mode;
    };

    _this.getLength = function(buffer) {
      return _data.length;
    };

    _this.write = function(buffer) {

      var s = _data;

      var i = 0;

      while (i + 1 < s.length) {
        buffer.put(
          getCode(s.charAt(i) ) * 45 +
          getCode(s.charAt(i + 1) ), 11);
        i += 2;
      }

      if (i < s.length) {
        buffer.put(getCode(s.charAt(i) ), 6);
      }
    };

    var getCode = function(c) {

      if ('0' <= c && c <= '9') {
        return c.charCodeAt(0) - '0'.charCodeAt(0);
      } else if ('A' <= c && c <= 'Z') {
        return c.charCodeAt(0) - 'A'.charCodeAt(0) + 10;
      } else {
        switch (c) {
        case ' ' : return 36;
        case '$' : return 37;
        case '%' : return 38;
        case '*' : return 39;
        case '+' : return 40;
        case '-' : return 41;
        case '.' : return 42;
        case '/' : return 43;
        case ':' : return 44;
        default :
          throw 'illegal char :' + c;
        }
      }
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // qr8BitByte
  //---------------------------------------------------------------------

  var qr8BitByte = function(data) {

    var _mode = QRMode.MODE_8BIT_BYTE;
    var _data = data;
    var _bytes = qrcode.stringToBytes(data);

    var _this = {};

    _this.getMode = function() {
      return _mode;
    };

    _this.getLength = function(buffer) {
      return _bytes.length;
    };

    _this.write = function(buffer) {
      for (var i = 0; i < _bytes.length; i += 1) {
        buffer.put(_bytes[i], 8);
      }
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // qrKanji
  //---------------------------------------------------------------------

  var qrKanji = function(data) {

    var _mode = QRMode.MODE_KANJI;
    var _data = data;
    var _bytes = qrcode.stringToBytes(data);

    !function(c, code) {
      // self test for sjis support.
      var test = qrcode.stringToBytes(c);
      if (test.length != 2 || ( (test[0] << 8) | test[1]) != code) {
        throw 'sjis not supported.';
      }
    }('\u53cb', 0x9746);

    var _this = {};

    _this.getMode = function() {
      return _mode;
    };

    _this.getLength = function(buffer) {
      return ~~(_bytes.length / 2);
    };

    _this.write = function(buffer) {

      var data = _bytes;

      var i = 0;

      while (i + 1 < data.length) {

        var c = ( (0xff & data[i]) << 8) | (0xff & data[i + 1]);

        if (0x8140 <= c && c <= 0x9FFC) {
          c -= 0x8140;
        } else if (0xE040 <= c && c <= 0xEBBF) {
          c -= 0xC140;
        } else {
          throw 'illegal char at ' + (i + 1) + '/' + c;
        }

        c = ( (c >>> 8) & 0xff) * 0xC0 + (c & 0xff);

        buffer.put(c, 13);

        i += 2;
      }

      if (i < data.length) {
        throw 'illegal char at ' + (i + 1);
      }
    };

    return _this;
  };

  //=====================================================================
  // GIF Support etc.
  //

  //---------------------------------------------------------------------
  // byteArrayOutputStream
  //---------------------------------------------------------------------

  var byteArrayOutputStream = function() {

    var _bytes = new Array();

    var _this = {};

    _this.writeByte = function(b) {
      _bytes.push(b & 0xff);
    };

    _this.writeShort = function(i) {
      _this.writeByte(i);
      _this.writeByte(i >>> 8);
    };

    _this.writeBytes = function(b, off, len) {
      off = off || 0;
      len = len || b.length;
      for (var i = 0; i < len; i += 1) {
        _this.writeByte(b[i + off]);
      }
    };

    _this.writeString = function(s) {
      for (var i = 0; i < s.length; i += 1) {
        _this.writeByte(s.charCodeAt(i) );
      }
    };

    _this.toByteArray = function() {
      return _bytes;
    };

    _this.toString = function() {
      var s = '';
      s += '[';
      for (var i = 0; i < _bytes.length; i += 1) {
        if (i > 0) {
          s += ',';
        }
        s += _bytes[i];
      }
      s += ']';
      return s;
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // base64EncodeOutputStream
  //---------------------------------------------------------------------

  var base64EncodeOutputStream = function() {

    var _buffer = 0;
    var _buflen = 0;
    var _length = 0;
    var _base64 = '';

    var _this = {};

    var writeEncoded = function(b) {
      _base64 += String.fromCharCode(encode(b & 0x3f) );
    };

    var encode = function(n) {
      if (n < 0) {
        // error.
      } else if (n < 26) {
        return 0x41 + n;
      } else if (n < 52) {
        return 0x61 + (n - 26);
      } else if (n < 62) {
        return 0x30 + (n - 52);
      } else if (n == 62) {
        return 0x2b;
      } else if (n == 63) {
        return 0x2f;
      }
      throw new Error('n:' + n);
    };

    _this.writeByte = function(n) {

      _buffer = (_buffer << 8) | (n & 0xff);
      _buflen += 8;
      _length += 1;

      while (_buflen >= 6) {
        writeEncoded(_buffer >>> (_buflen - 6) );
        _buflen -= 6;
      }
    };

    _this.flush = function() {

      if (_buflen > 0) {
        writeEncoded(_buffer << (6 - _buflen) );
        _buffer = 0;
        _buflen = 0;
      }

      if (_length % 3 != 0) {
        // padding
        var padlen = 3 - _length % 3;
        for (var i = 0; i < padlen; i += 1) {
          _base64 += '=';
        }
      }
    };

    _this.toString = function() {
      return _base64;
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // base64DecodeInputStream
  //---------------------------------------------------------------------

  var base64DecodeInputStream = function(str) {

    var _str = str;
    var _pos = 0;
    var _buffer = 0;
    var _buflen = 0;

    var _this = {};

    _this.read = function() {

      while (_buflen < 8) {

        if (_pos >= _str.length) {
          if (_buflen == 0) {
            return -1;
          }
          throw new Error('unexpected end of file./' + _buflen);
        }

        var c = _str.charAt(_pos);
        _pos += 1;

        if (c == '=') {
          _buflen = 0;
          return -1;
        } else if (c.match(/^\s$/) ) {
          // ignore if whitespace.
          continue;
        }

        _buffer = (_buffer << 6) | decode(c.charCodeAt(0) );
        _buflen += 6;
      }

      var n = (_buffer >>> (_buflen - 8) ) & 0xff;
      _buflen -= 8;
      return n;
    };

    var decode = function(c) {
      if (0x41 <= c && c <= 0x5a) {
        return c - 0x41;
      } else if (0x61 <= c && c <= 0x7a) {
        return c - 0x61 + 26;
      } else if (0x30 <= c && c <= 0x39) {
        return c - 0x30 + 52;
      } else if (c == 0x2b) {
        return 62;
      } else if (c == 0x2f) {
        return 63;
      } else {
        throw new Error('c:' + c);
      }
    };

    return _this;
  };

  //---------------------------------------------------------------------
  // gifImage (B/W)
  //---------------------------------------------------------------------

  var gifImage = function(width, height) {

    var _width = width;
    var _height = height;
    var _data = new Array(width * height);

    var _this = {};

    _this.setPixel = function(x, y, pixel) {
      _data[y * _width + x] = pixel;
    };

    _this.write = function(out) {

      //---------------------------------
      // GIF Signature

      out.writeString('GIF87a');

      //---------------------------------
      // Screen Descriptor

      out.writeShort(_width);
      out.writeShort(_height);

      out.writeByte(0x80); // 2bit
      out.writeByte(0);
      out.writeByte(0);

      //---------------------------------
      // Global Color Map

      // black
      out.writeByte(0x00);
      out.writeByte(0x00);
      out.writeByte(0x00);

      // white
      out.writeByte(0xff);
      out.writeByte(0xff);
      out.writeByte(0xff);

      //---------------------------------
      // Image Descriptor

      out.writeString(',');
      out.writeShort(0);
      out.writeShort(0);
      out.writeShort(_width);
      out.writeShort(_height);
      out.writeByte(0);

      //---------------------------------
      // Local Color Map

      //---------------------------------
      // Raster Data

      var lzwMinCodeSize = 2;
      var raster = getLZWRaster(lzwMinCodeSize);

      out.writeByte(lzwMinCodeSize);

      var offset = 0;

      while (raster.length - offset > 255) {
        out.writeByte(255);
        out.writeBytes(raster, offset, 255);
        offset += 255;
      }

      out.writeByte(raster.length - offset);
      out.writeBytes(raster, offset, raster.length - offset);
      out.writeByte(0x00);

      //---------------------------------
      // GIF Terminator
      out.writeString(';');
    };

    var bitOutputStream = function(out) {

      var _out = out;
      var _bitLength = 0;
      var _bitBuffer = 0;

      var _this = {};

      _this.write = function(data, length) {

        if ( (data >>> length) != 0) {
          throw new Error('length over');
        }

        while (_bitLength + length >= 8) {
          _out.writeByte(0xff & ( (data << _bitLength) | _bitBuffer) );
          length -= (8 - _bitLength);
          data >>>= (8 - _bitLength);
          _bitBuffer = 0;
          _bitLength = 0;
        }

        _bitBuffer = (data << _bitLength) | _bitBuffer;
        _bitLength = _bitLength + length;
      };

      _this.flush = function() {
        if (_bitLength > 0) {
          _out.writeByte(_bitBuffer);
        }
      };

      return _this;
    };

    var getLZWRaster = function(lzwMinCodeSize) {

      var clearCode = 1 << lzwMinCodeSize;
      var endCode = (1 << lzwMinCodeSize) + 1;
      var bitLength = lzwMinCodeSize + 1;

      // Setup LZWTable
      var table = lzwTable();

      for (var i = 0; i < clearCode; i += 1) {
        table.add(String.fromCharCode(i) );
      }
      table.add(String.fromCharCode(clearCode) );
      table.add(String.fromCharCode(endCode) );

      var byteOut = byteArrayOutputStream();
      var bitOut = bitOutputStream(byteOut);

      // clear code
      bitOut.write(clearCode, bitLength);

      var dataIndex = 0;

      var s = String.fromCharCode(_data[dataIndex]);
      dataIndex += 1;

      while (dataIndex < _data.length) {

        var c = String.fromCharCode(_data[dataIndex]);
        dataIndex += 1;

        if (table.contains(s + c) ) {

          s = s + c;

        } else {

          bitOut.write(table.indexOf(s), bitLength);

          if (table.size() < 0xfff) {

            if (table.size() == (1 << bitLength) ) {
              bitLength += 1;
            }

            table.add(s + c);
          }

          s = c;
        }
      }

      bitOut.write(table.indexOf(s), bitLength);

      // end code
      bitOut.write(endCode, bitLength);

      bitOut.flush();

      return byteOut.toByteArray();
    };

    var lzwTable = function() {

      var _map = {};
      var _size = 0;

      var _this = {};

      _this.add = function(key) {
        if (_this.contains(key) ) {
          throw new Error('dup key:' + key);
        }
        _map[key] = _size;
        _size += 1;
      };

      _this.size = function() {
        return _size;
      };

      _this.indexOf = function(key) {
        return _map[key];
      };

      _this.contains = function(key) {
        return typeof _map[key] != 'undefined';
      };

      return _this;
    };

    return _this;
  };

  var createImgTag = function(width, height, getPixel, alt) {

    var gif = gifImage(width, height);
    for (var y = 0; y < height; y += 1) {
      for (var x = 0; x < width; x += 1) {
        gif.setPixel(x, y, getPixel(x, y) );
      }
    }

    var b = byteArrayOutputStream();
    gif.write(b);

    var base64 = base64EncodeOutputStream();
    var bytes = b.toByteArray();
    for (var i = 0; i < bytes.length; i += 1) {
      base64.writeByte(bytes[i]);
    }
    base64.flush();

    var img = '';
    img += '<img';
    img += '\u0020src="';
    img += 'data:image/gif;base64,';
    img += base64;
    img += '"';
    img += '\u0020width="';
    img += width;
    img += '"';
    img += '\u0020height="';
    img += height;
    img += '"';
    if (alt) {
      img += '\u0020alt="';
      img += alt;
      img += '"';
    }
    img += '/>';

    return img;
  };

  //---------------------------------------------------------------------
  // returns qrcode function.

  return qrcode;
}();

</script>
<script>
/*
 * sheet-renderer.js
 * ---------------------------------------------------------------------
 * สร้างกระดาษคำตอบ OMR (สเกล 14.7 x 14.7 cm, viewBox 1470x1470, ระบบ 100 units = 1cm)
 * เป็นชุดฟังก์ชันที่ใช้ซ้ำได้ต่อนักเรียน 1 คน / 1 แผ่น เพื่อพิมพ์ทั้งชุดใน print_sheets.php
 * โครงสร้าง SVG อ้างอิงจาก omr_14_7.html (เทมเพลตหลักที่ใช้แสดงตัวอย่างในระบบ)
 *
 * ต้องโหลด qrcode.lib.js ก่อนไฟล์นี้ ถ้าต้องการฝัง QR รายบุคคล (opts.qrText)
 *
 * การใช้งานหลัก:
 *   mountOMRSheet(containerEl, {
 *     uid: 'sheet-101',              // ต้องไม่ซ้ำกันถ้าจะแสดงหลายแผ่นในหน้าเดียว
 *     schoolName: '', academicYear: '',
 *     studentName: 'เด็กชายทดสอบ ใจดี', studentClassName: 'ม.4/1', studentNo: '05',
 *     subjectName: 'กลางภาค คณิตศาสตร์ ม.4',
 *     examDay: '', examMonth: '', examYear: '',
 *     qrText: 'OMR1|3|101'           // รูปแบบ: OMR1|{exam_id}|{student_id}
 *   });
 * ---------------------------------------------------------------------
 */

const OMR_NS = "http://www.w3.org/2000/svg";

function omrEscapeXml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

// สร้าง markup ของกระดาษทั้งแผ่น (ยกเว้นเนื้อหาช่องกาคำตอบ/ตอนที่ 1-8 ซึ่งจะเติมด้วย JS ทีหลัง)
function omrSheetTemplate(opts) {
    const o = Object.assign({
        uid: 'sheet',
        schoolName: '', academicYear: '',
        studentName: '', studentClassName: '', studentNo: '',
        subjectName: '', examDay: '', examMonth: '', examYear: ''
    }, opts || {});

    // เติมค่าที่กรอกไว้แล้ว (ถ้ามี) เหนือเส้นประแต่ละช่อง แบบลายมือกรอกไว้ล่วงหน้า
    function valueText(x, y, value) {
        if (!value) return '';
        return `<text x="${x}" y="${y - 6}" style="font-family:'Sarabun',sans-serif; font-size:19px; font-weight:600; fill:#0f172a;">${omrEscapeXml(value)}</text>`;
    }

    return `
<svg id="omr-svg-${o.uid}" xmlns="${OMR_NS}" viewBox="0 0 1470 1470" width="14.7cm" height="14.7cm">
    <defs>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&amp;display=swap');
            .txt-title { font-family: 'Sarabun', sans-serif; font-size: 45px; font-weight: 700; fill: #197b42; text-anchor: middle; }
            .txt-normal { font-family: 'Sarabun', sans-serif; font-size: 22px; font-weight: 600; fill: #197b42; }
            .txt-bold { font-family: 'Sarabun', sans-serif; font-size: 22px; font-weight: 700; fill: #197b42; text-anchor: middle; }
            .txt-col-head { font-family: 'Sarabun', sans-serif; font-size: 19px; font-weight: 700; fill: #197b42; text-anchor: middle; }
            .txt-small { font-family: 'Sarabun', sans-serif; font-size: 19px; font-weight: 700; fill: #197b42; text-anchor: middle; }
            .line-green { stroke: #197b42; stroke-width: 2; }
            .line-green-thick { stroke: #197b42; stroke-width: 4; }
            .line-dotted { stroke: #197b42; stroke-width: 2; stroke-dasharray: 4 6; }
            .bg-light-green { fill: #d9ead3; }
            .bg-white { fill: #ffffff; }
        </style>
    </defs>

    <rect width="1470" height="1470" fill="#ffffff" />

    <g transform="translate(135, 120)">
        <rect x="0" y="0" width="930" height="70" class="bg-light-green line-green-thick" />
        <text x="465" y="48" class="txt-title">กระดาษคำตอบ</text>

        <g transform="translate(0, 100)">
            <text x="0" y="22" class="txt-normal">โรงเรียน</text>
            <line x1="80" y1="22" x2="480" y2="22" class="line-dotted" />
            ${valueText(85, 22, o.schoolName)}
            <text x="500" y="22" class="txt-normal">ปีการศึกษา</text>
            <line x1="600" y1="22" x2="930" y2="22" class="line-dotted" />
            ${valueText(605, 22, o.academicYear)}

            <text x="0" y="72" class="txt-normal">ชื่อ</text>
            <line x1="40" y1="72" x2="550" y2="72" class="line-dotted" />
            ${valueText(45, 72, o.studentName)}
            <text x="570" y="72" class="txt-normal">ชั้น</text>
            <line x1="610" y1="72" x2="720" y2="72" class="line-dotted" />
            ${valueText(615, 72, o.studentClassName)}
            <text x="740" y="72" class="txt-normal">เลขที่</text>
            <line x1="800" y1="72" x2="930" y2="72" class="line-dotted" />
            ${valueText(805, 72, o.studentNo)}

            <text x="0" y="122" class="txt-normal">วิชา</text>
            <line x1="50" y1="122" x2="450" y2="122" class="line-dotted" />
            ${valueText(55, 122, o.subjectName)}
            <text x="480" y="122" class="txt-normal">วันที่</text>
            <line x1="530" y1="122" x2="600" y2="122" class="line-dotted" />
            ${valueText(535, 122, o.examDay)}
            <text x="620" y="122" class="txt-normal">เดือน</text>
            <line x1="680" y1="122" x2="800" y2="122" class="line-dotted" />
            ${valueText(685, 122, o.examMonth)}
            <text x="820" y="122" class="txt-normal">พ.ศ.</text>
            <line x1="860" y1="122" x2="930" y2="122" class="line-dotted" />
            ${valueText(865, 122, o.examYear)}
        </g>

        <g transform="translate(950, 0)">
            <rect x="0" y="0" width="250" height="230" class="bg-light-green line-green-thick" />
            <text x="125" y="45" class="txt-bold" style="font-size: 26px;">คะแนนรวม</text>
            <line x1="0" y1="65" x2="250" y2="65" class="line-green" />
        </g>

        <g transform="translate(0, 260)">
            <rect x="0" y="0" width="1200" height="810" class="line-green-thick" fill="none" />
            <g id="svg-grid-content-${o.uid}"></g>
        </g>

        <g transform="translate(0, 1110)">
            <rect x="0" y="0" width="1200" height="120" class="line-green-thick" fill="none" />
            <g id="svg-parts-content-${o.uid}"></g>
        </g>
    </g>

    <rect x="117.5" y="497.5" width="35" height="35" fill="#000000" />
    <rect x="1317.5" y="497.5" width="35" height="35" fill="#000000" />
    <rect x="117.5" y="1172.5" width="35" height="35" fill="#000000" />
    <rect x="1317.5" y="1172.5" width="35" height="35" fill="#000000" />
</svg>`.trim();
}

// สร้างตารางกาคำตอบ 4 คอลัมน์ x 15 แถว x 5 ตัวเลือก (60 ข้อ) ลงใน gridGroupEl ที่ให้มา
function buildGridContent(ns, gridGroupEl) {
    const colWidth = 300, optWidth = 45, rowHeight = 45, qWidth = 75, headerHeight = 135, itemsPerCol = 15;

    for (let col = 0; col < 4; col++) {
        let colOffsetX = col * colWidth;
        let startItem = (col * itemsPerCol) + 1;

        if (col > 0) {
            let colLine = document.createElementNS(ns, 'line');
            colLine.setAttribute('x1', colOffsetX); colLine.setAttribute('y1', 0);
            colLine.setAttribute('x2', colOffsetX); colLine.setAttribute('y2', headerHeight + (itemsPerCol * rowHeight));
            colLine.setAttribute('class', 'line-green-thick');
            gridGroupEl.appendChild(colLine);
        }

        for (let h = 0; h < 3; h++) {
            let bgH = document.createElementNS(ns, 'rect');
            bgH.setAttribute('x', colOffsetX); bgH.setAttribute('y', h * rowHeight);
            bgH.setAttribute('width', colWidth); bgH.setAttribute('height', rowHeight);
            bgH.setAttribute('class', (h === 1) ? 'bg-white line-green' : 'bg-light-green line-green');
            gridGroupEl.appendChild(bgH);
        }

        let bgQ = document.createElementNS(ns, 'rect');
        bgQ.setAttribute('x', colOffsetX); bgQ.setAttribute('y', 0);
        bgQ.setAttribute('width', qWidth); bgQ.setAttribute('height', headerHeight);
        bgQ.setAttribute('class', 'bg-white line-green');
        gridGroupEl.appendChild(bgQ);

        let textQ = document.createElementNS(ns, 'text');
        textQ.setAttribute('x', colOffsetX + (qWidth / 2)); textQ.setAttribute('y', headerHeight / 2 + 8);
        textQ.setAttribute('class', 'txt-bold'); textQ.textContent = 'ข้อ';
        gridGroupEl.appendChild(textQ);

        const labelsTh = ['ก', 'ข', 'ค', 'ง', 'จ'], labelsEn = ['A', 'B', 'C', 'D', 'E'], labelsNum = ['1', '2', '3', '4', '5'];

        for (let opt = 0; opt < 5; opt++) {
            let optX = colOffsetX + qWidth + (opt * optWidth);
            let textCX = optX + (optWidth / 2);

            let vline = document.createElementNS(ns, 'line');
            vline.setAttribute('x1', optX); vline.setAttribute('y1', 0);
            vline.setAttribute('x2', optX); vline.setAttribute('y2', headerHeight);
            vline.setAttribute('class', 'line-green');
            gridGroupEl.appendChild(vline);

            let tTh = document.createElementNS(ns, 'text'); tTh.setAttribute('x', textCX); tTh.setAttribute('y', 31); tTh.setAttribute('class', 'txt-col-head'); tTh.textContent = labelsTh[opt]; gridGroupEl.appendChild(tTh);
            let tEn = document.createElementNS(ns, 'text'); tEn.setAttribute('x', textCX); tEn.setAttribute('y', 31 + rowHeight); tEn.setAttribute('class', 'txt-col-head'); tEn.textContent = labelsEn[opt]; gridGroupEl.appendChild(tEn);
            let tNumH = document.createElementNS(ns, 'text'); tNumH.setAttribute('x', textCX); tNumH.setAttribute('y', 31 + (rowHeight * 2)); tNumH.setAttribute('class', 'txt-col-head'); tNumH.textContent = labelsNum[opt]; gridGroupEl.appendChild(tNumH);
        }

        for (let r = 0; r < itemsPerCol; r++) {
            let rowY = headerHeight + (r * rowHeight);
            let itemNum = startItem + r;

            let hline = document.createElementNS(ns, 'line');
            hline.setAttribute('x1', colOffsetX); hline.setAttribute('y1', rowY);
            hline.setAttribute('x2', colOffsetX + colWidth); hline.setAttribute('y2', rowY);
            hline.setAttribute('class', 'line-green');
            gridGroupEl.appendChild(hline);

            let vlineQ = document.createElementNS(ns, 'line');
            vlineQ.setAttribute('x1', colOffsetX + qWidth); vlineQ.setAttribute('y1', rowY);
            vlineQ.setAttribute('x2', colOffsetX + qWidth); vlineQ.setAttribute('y2', rowY + rowHeight);
            vlineQ.setAttribute('class', 'line-green');
            gridGroupEl.appendChild(vlineQ);

            let tNum = document.createElementNS(ns, 'text');
            tNum.setAttribute('x', colOffsetX + (qWidth / 2)); tNum.setAttribute('y', rowY + (rowHeight / 2) + 8);
            tNum.setAttribute('class', 'txt-bold'); tNum.textContent = itemNum;
            gridGroupEl.appendChild(tNum);

            for (let opt = 1; opt < 5; opt++) {
                let optX = colOffsetX + qWidth + (opt * optWidth);
                let vlineOpt = document.createElementNS(ns, 'line');
                vlineOpt.setAttribute('x1', optX); vlineOpt.setAttribute('y1', rowY);
                vlineOpt.setAttribute('x2', optX); vlineOpt.setAttribute('y2', rowY + rowHeight);
                vlineOpt.setAttribute('class', 'line-green');
                gridGroupEl.appendChild(vlineOpt);
            }
        }
    }
}

// สร้างแถวล่าง "ตอนที่ 1-7" + ช่อง QR (ตอนที่ 8 เดิม) ลงใน partsGroupEl ที่ให้มา
// ถ้ามี opts.qrText จะเรนเดอร์ QR จริงทันที ไม่ต้องรอ placeholder
function buildPartsContent(ns, partsGroupEl, opts) {
    opts = opts || {};
    const partWidth = 150, partHeight = 120;
    const QR_SLOT_INDEX = 8;
    let slot = null;

    for (let i = 1; i <= 8; i++) {
        let pX = (i - 1) * partWidth;

        if (i > 1) {
            let vline = document.createElementNS(ns, 'line');
            vline.setAttribute('x1', pX); vline.setAttribute('y1', 0);
            vline.setAttribute('x2', pX); vline.setAttribute('y2', partHeight);
            vline.setAttribute('class', 'line-green');
            partsGroupEl.appendChild(vline);
        }

        if (i === QR_SLOT_INDEX) {
            let tQrLabel = document.createElementNS(ns, 'text');
            tQrLabel.setAttribute('x', pX + (partWidth / 2)); tQrLabel.setAttribute('y', 16);
            tQrLabel.setAttribute('class', 'txt-small'); tQrLabel.setAttribute('style', 'font-size:14px;');
            tQrLabel.textContent = 'รหัส QR';
            partsGroupEl.appendChild(tQrLabel);

            const qrBoxSize = 96;
            const qrBoxX = pX + (partWidth - qrBoxSize) / 2;
            const qrBoxY = 22;
            slot = { x: qrBoxX, y: qrBoxY, size: qrBoxSize };

            if (opts.qrText) {
                renderQRIntoSlot(ns, partsGroupEl, slot, opts.qrText);
            } else {
                const qrPlaceholder = document.createElementNS(ns, 'rect');
                qrPlaceholder.setAttribute('id', 'qr-slot-placeholder');
                qrPlaceholder.setAttribute('x', qrBoxX); qrPlaceholder.setAttribute('y', qrBoxY);
                qrPlaceholder.setAttribute('width', qrBoxSize); qrPlaceholder.setAttribute('height', qrBoxSize);
                qrPlaceholder.setAttribute('fill', 'none');
                qrPlaceholder.setAttribute('stroke', '#197b42');
                qrPlaceholder.setAttribute('stroke-width', '2');
                qrPlaceholder.setAttribute('stroke-dasharray', '4 5');
                partsGroupEl.appendChild(qrPlaceholder);

                let tQrHint = document.createElementNS(ns, 'text');
                tQrHint.setAttribute('x', pX + (partWidth / 2)); tQrHint.setAttribute('y', qrBoxY + qrBoxSize / 2 - 4);
                tQrHint.setAttribute('class', 'txt-small qr-slot-hint-text'); tQrHint.setAttribute('style', 'font-size:11px;');
                tQrHint.textContent = 'สร้างตอน';
                partsGroupEl.appendChild(tQrHint);
                let tQrHint2 = document.createElementNS(ns, 'text');
                tQrHint2.setAttribute('x', pX + (partWidth / 2)); tQrHint2.setAttribute('y', qrBoxY + qrBoxSize / 2 + 12);
                tQrHint2.setAttribute('class', 'txt-small qr-slot-hint-text'); tQrHint2.setAttribute('style', 'font-size:11px;');
                tQrHint2.textContent = 'พิมพ์รายคน';
                partsGroupEl.appendChild(tQrHint2);
            }
            continue;
        }

        let tPart = document.createElementNS(ns, 'text');
        tPart.setAttribute('x', pX + (partWidth / 2)); tPart.setAttribute('y', 50);
        tPart.setAttribute('class', 'txt-small'); tPart.textContent = 'ตอนที่ ' + i;
        partsGroupEl.appendChild(tPart);

        let dline = document.createElementNS(ns, 'line');
        dline.setAttribute('x1', pX + 15); dline.setAttribute('y1', 95);
        dline.setAttribute('x2', pX + partWidth - 15); dline.setAttribute('y2', 95);
        dline.setAttribute('class', 'line-dotted');
        partsGroupEl.appendChild(dline);
    }

    return { slot };
}

// เรนเดอร์ QR (จาก qrcode.lib.js) ลงในกลุ่ม/ตำแหน่งที่กำหนด — ผูกกับ parentGroupEl แต่ละแผ่นแยกกัน
// เพื่อไม่ให้ชนกันตอนแสดงหลายแผ่นพร้อมกันในหน้าเดียว (print_sheets.php)
function renderQRIntoSlot(ns, parentGroupEl, slot, text) {
    if (typeof qrcode === 'undefined' || !text) return false;

    parentGroupEl.querySelectorAll('#qr-slot-placeholder, .qr-slot-hint-text, #qr-slot-rendered')
        .forEach(el => el.remove());

    const qr = qrcode(0, 'M'); // typeNumber 0 = auto เลือกขนาดให้พอดีกับข้อมูล
    qr.addData(String(text));
    qr.make();

    const count = qr.getModuleCount();
    const cell = slot.size / count;
    const qrGroup = document.createElementNS(ns, 'g');
    qrGroup.setAttribute('id', 'qr-slot-rendered');

    const bg = document.createElementNS(ns, 'rect');
    bg.setAttribute('x', slot.x); bg.setAttribute('y', slot.y);
    bg.setAttribute('width', slot.size); bg.setAttribute('height', slot.size);
    bg.setAttribute('fill', '#ffffff');
    qrGroup.appendChild(bg);

    for (let r = 0; r < count; r++) {
        for (let c = 0; c < count; c++) {
            if (!qr.isDark(r, c)) continue;
            const px = document.createElementNS(ns, 'rect');
            px.setAttribute('x', slot.x + c * cell);
            px.setAttribute('y', slot.y + r * cell);
            px.setAttribute('width', cell);
            px.setAttribute('height', cell);
            px.setAttribute('fill', '#000000');
            qrGroup.appendChild(px);
        }
    }
    parentGroupEl.appendChild(qrGroup);
    return true;
}

// ประกอบร่างกระดาษ 1 แผ่นให้สมบูรณ์ ใส่ลงใน containerEl ที่ให้มา (เช่น <div>)
// คืนค่า { svgEl, gridGroupEl, partsGroupEl, slot } เผื่ออยากอ้างอิงภายหลัง
function mountOMRSheet(containerEl, opts) {
    opts = Object.assign({ uid: 'sheet-' + Math.random().toString(36).slice(2, 9) }, opts || {});
    containerEl.innerHTML = omrSheetTemplate(opts);

    const svgEl = containerEl.querySelector(`#omr-svg-${opts.uid}`);
    const gridGroupEl = containerEl.querySelector(`#svg-grid-content-${opts.uid}`);
    const partsGroupEl = containerEl.querySelector(`#svg-parts-content-${opts.uid}`);

    buildGridContent(OMR_NS, gridGroupEl);
    const { slot } = buildPartsContent(OMR_NS, partsGroupEl, { qrText: opts.qrText });

    return { svgEl, gridGroupEl, partsGroupEl, slot };
}
</script>

<style>
    #sheetsPreview { background: #e2e8f0; border-radius: 1rem; padding: 1.5rem; }
    .print-page {
        background: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        margin: 0 auto 24px auto;
        width: 21cm; min-height: 29.7cm;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1cm;
        padding: 1cm 0;
    }
    .print-page .sheet-slot { width: 14.7cm; height: 14.7cm; flex-shrink: 0; }
    .print-page .sheet-slot svg { width: 100%; height: 100%; display: block; }
    .sheet-caption { font-size: 11px; color: #64748b; text-align: center; margin-top: 4px; }

    @media print {
        body * { visibility: hidden; }
        #printRoot, #printRoot * { visibility: visible; }
        #printRoot { position: absolute; left: 0; top: 0; margin: 0; padding: 0; }
        .print-page {
            box-shadow: none !important; margin: 0 !important;
            width: 21cm; height: 29.7cm; page-break-after: always;
        }
        .print-page:last-child { page-break-after: auto; }
        .sheet-caption { display: none; }
        @page { size: A4; margin: 0; }
    }
</style>

<div class="max-w-4xl mx-auto space-y-6 pb-20 pt-4">

    <div id="pageErrorBanner" class="hidden bg-red-50 border-2 border-red-200 text-red-700 text-sm font-medium rounded-2xl p-4 whitespace-pre-wrap"></div>

    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-100 shadow-sm">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl shrink-0">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900">พิมพ์กระดาษคำตอบทั้งชุด</h1>
                <p class="text-sm text-slate-500 mt-1">แต่ละแผ่นจะฝัง QR รหัสประจำตัวนักเรียนไว้ที่ช่อง "ตอนที่ 8" ให้อัตโนมัติ ใช้คู่กับหน้าตรวจข้อสอบเพื่อจับคู่คะแนนโดยไม่ต้องเลือกชื่อเอง</p>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">เลือกชุดข้อสอบ</label>
                <select id="examSelector" class="w-full bg-slate-50 border-2 border-slate-200 text-slate-800 text-sm font-medium rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 focus:bg-white">
                    <option value="">-- เลือกข้อสอบ --</option>
                    <?php foreach ($exams as $ex): ?>
                        <option value="<?= (int)$ex['id'] ?>" <?= ($preselect_exam_id === (int)$ex['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ex['exam_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">จำนวนแผ่นต่อ 1 หน้า A4</label>
                <select id="perPageSelector" class="w-full bg-slate-50 border-2 border-slate-200 text-slate-800 text-sm font-medium rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 focus:bg-white">
                    <option value="1">1 แผ่นต่อหน้า (แนะนำ อ่านง่ายสุด)</option>
                    <option value="2">2 แผ่นต่อหน้า (ประหยัดกระดาษ)</option>
                </select>
            </div>
        </div>

        <div id="studentPickerWrap" class="hidden">
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-semibold text-slate-900">เลือกนักเรียนที่จะพิมพ์</label>
                <div class="flex gap-2 text-xs">
                    <button type="button" id="selectAllBtn" class="text-blue-600 font-semibold hover:underline">เลือกทั้งหมด</button>
                    <span class="text-slate-300">|</span>
                    <button type="button" id="selectNoneBtn" class="text-slate-500 font-semibold hover:underline">ไม่เลือกเลย</button>
                </div>
            </div>
            <div id="studentCheckList" class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm mb-4"></div>

            <button id="generateBtn" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles"></i> สร้างกระดาษคำตอบ
            </button>
        </div>

        <p id="emptyHint" class="text-sm text-slate-400 text-center py-4">เลือกข้อสอบก่อน ระบบจะโหลดรายชื่อนักเรียนของข้อสอบนั้นให้อัตโนมัติ</p>
    </div>

    <div id="previewWrap" class="hidden bg-white p-6 sm:p-8 rounded-3xl border border-slate-100 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">ตัวอย่างก่อนพิมพ์</h2>
                <p class="text-xs text-slate-500">ตรวจสอบชื่อ/เลขที่ให้ถูกต้อง แล้วกดสั่งพิมพ์ — ระบบจะจัดเรียงลง A4 ให้เอง</p>
            </div>
            <button id="printNowBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl flex items-center justify-center gap-2 shadow-sm">
                <i class="fa-solid fa-print"></i> สั่งพิมพ์ทั้งชุด
            </button>
        </div>
        <div id="sheetsPreview">
            <div id="printRoot"></div>
        </div>
    </div>
</div>

<script>
    const examData = <?= json_encode($exams) ?>;
    const studentsByExam = <?= json_encode($students_by_exam) ?>;
    const EXAM_ID_PLACEHOLDER = null; // เผื่อใช้ debug

    const examSelector = document.getElementById('examSelector');
    const perPageSelector = document.getElementById('perPageSelector');
    const studentPickerWrap = document.getElementById('studentPickerWrap');
    const studentCheckList = document.getElementById('studentCheckList');
    const emptyHint = document.getElementById('emptyHint');
    const generateBtn = document.getElementById('generateBtn');
    const previewWrap = document.getElementById('previewWrap');
    const printRoot = document.getElementById('printRoot');
    const printNowBtn = document.getElementById('printNowBtn');
    const pageErrorBanner = document.getElementById('pageErrorBanner');

    // แสดง error ให้เห็นบนหน้าจอโดยตรง (เผื่ออุปกรณ์เปิด DevTools ไม่ได้)
    function showPageError(msg) {
        pageErrorBanner.textContent = 'เกิดข้อผิดพลาด: ' + msg;
        pageErrorBanner.classList.remove('hidden');
        pageErrorBanner.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ดักจับ error ที่หลุดออกมาจากทุกจุดในหน้านี้ (เช่น ไฟล์ .js โหลดไม่สำเร็จ)
    window.addEventListener('error', (e) => {
        showPageError((e.message || 'ไม่ทราบสาเหตุ') + (e.filename ? '\n(ไฟล์: ' + e.filename + ' บรรทัด ' + e.lineno + ')' : ''));
    });

    // เช็คไฟล์ dependency ที่จำเป็นตั้งแต่โหลดหน้า จะได้รู้ทันทีถ้ามีไฟล์ขาด
    document.addEventListener('DOMContentLoaded', () => {
        const missing = [];
        if (typeof mountOMRSheet === 'undefined') missing.push('sheet-renderer.js (หาไฟล์ไม่เจอ หรือไฟล์มี error อยู่ข้างใน)');
        if (typeof qrcode === 'undefined') missing.push('qrcode.lib.js (หาไฟล์ไม่เจอ QR โค้ดจะไม่ถูกวาด)');
        if (missing.length) {
            showPageError('ไม่พบไฟล์ที่จำเป็นต่อไปนี้ กรุณาตรวจสอบว่าไฟล์อยู่ในโฟลเดอร์เดียวกับ print_sheets.php:\n- ' + missing.join('\n- '));
        }
    });

    function currentStudents() {
        const examId = examSelector.value;
        return examId && studentsByExam[examId] ? studentsByExam[examId] : [];
    }

    function renderStudentChecklist() {
        const students = currentStudents();
        studentCheckList.innerHTML = '';
        if (!students.length) {
            studentCheckList.innerHTML = '<p class="col-span-full text-slate-400 text-xs py-2">ข้อสอบนี้ยังไม่มีรายชื่อนักเรียน (ไปอัปโหลด CSV ที่หน้าสร้างข้อสอบก่อน)</p>';
            return;
        }
        students.forEach(st => {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-2.5 py-2 cursor-pointer';
            label.innerHTML = `
                <input type="checkbox" class="student-check accent-blue-600" value="${st.id}" checked>
                <span class="text-xs text-slate-700">${st.student_no}. ${st.student_name}</span>
            `;
            studentCheckList.appendChild(label);
        });
    }

    examSelector.addEventListener('change', () => {
        previewWrap.classList.add('hidden');
        printRoot.innerHTML = '';
        if (!examSelector.value) {
            studentPickerWrap.classList.add('hidden');
            emptyHint.classList.remove('hidden');
            return;
        }
        studentPickerWrap.classList.remove('hidden');
        emptyHint.classList.add('hidden');
        renderStudentChecklist();
    });

    document.getElementById('selectAllBtn').addEventListener('click', () => {
        studentCheckList.querySelectorAll('.student-check').forEach(cb => cb.checked = true);
    });
    document.getElementById('selectNoneBtn').addEventListener('click', () => {
        studentCheckList.querySelectorAll('.student-check').forEach(cb => cb.checked = false);
    });

    generateBtn.addEventListener('click', () => {
        pageErrorBanner.classList.add('hidden');
        try {
            const examId = examSelector.value;
            const exam = examData.find(e => e.id == examId);
            if (!exam) return;

            const selectedIds = Array.from(studentCheckList.querySelectorAll('.student-check:checked')).map(cb => cb.value);
            const students = currentStudents().filter(st => selectedIds.includes(String(st.id)));

            if (!students.length) {
                alert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
                return;
            }

            if (typeof mountOMRSheet === 'undefined') {
                throw new Error('ไม่พบฟังก์ชัน mountOMRSheet — ไฟล์ sheet-renderer.js ไม่ถูกโหลด กรุณาตรวจสอบว่าไฟล์นี้อยู่ในโฟลเดอร์เดียวกับ print_sheets.php บนเซิร์ฟเวอร์');
            }

            const perPage = parseInt(perPageSelector.value, 10) || 1;
            printRoot.innerHTML = '';

            for (let i = 0; i < students.length; i += perPage) {
                const pageStudents = students.slice(i, i + perPage);
                const pageEl = document.createElement('div');
                pageEl.className = 'print-page';

                pageStudents.forEach(st => {
                    const wrap = document.createElement('div');

                    const slotEl = document.createElement('div');
                    slotEl.className = 'sheet-slot';
                    wrap.appendChild(slotEl);

                    const caption = document.createElement('div');
                    caption.className = 'sheet-caption';
                    caption.textContent = `${st.student_no}. ${st.student_name}`;
                    wrap.appendChild(caption);

                    pageEl.appendChild(wrap);

                    mountOMRSheet(slotEl, {
                        uid: 'sheet-' + st.id,
                        studentName: st.student_name,
                        studentNo: st.student_no,
                        subjectName: exam.exam_name,
                        qrText: `OMR1|${exam.id}|${st.id}`
                    });
                });

                printRoot.appendChild(pageEl);
            }

            previewWrap.classList.remove('hidden');
            previewWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            showPageError(err.message || String(err));
        }
    });

    printNowBtn.addEventListener('click', () => window.print());

    // ถ้ามีการเลือกข้อสอบไว้ล่วงหน้า (เปิดมาจากปุ่มในหน้าสร้างข้อสอบ) ให้เริ่มโหลดรายชื่อทันที
    if (examSelector.value) {
        examSelector.dispatchEvent(new Event('change'));
    }
</script>
