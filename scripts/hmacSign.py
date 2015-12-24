import sys, hashlib,struct

def fill(value, lenght, fillByte):
    if len(value) >= lenght:
        return value
    else:
        fillSize = lenght - len(value)
    return value + chr(fillByte) * fillSize

def doXOr(s, value):
    slist = list(s)
    for index in xrange(len(slist)):
        slist[index] = chr(ord(slist[index]) ^ value)
    return "".join(slist)

def hmacSign(aValue, aKey):
    keyb   = struct.pack("%ds" % len(aKey), aKey)
    value  = struct.pack("%ds" % len(aValue), aValue)
    k_ipad = doXOr(keyb, 0x36)
    k_opad = doXOr(keyb, 0x5c)
    k_ipad = fill(k_ipad, 64, 54)
    k_opad = fill(k_opad, 64, 92)
    m = hashlib.md5()
    m.update(k_ipad)
    m.update(value)
    dg = m.digest()
    m = hashlib.md5()
    m.update(k_opad)
    subStr = dg[0:16]
    m.update(subStr)
    print m.hexdigest()
hmacSign(sys.argv[1], sys.argv[2])
