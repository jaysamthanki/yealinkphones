# Yealink Phones Module for FreePBX

A FreePBX module for managing and provisioning Yealink SIP phones (T3x, T4x, T5x series).

## Features

- **Phone Management** - Centralized inventory of Yealink phones with MAC address registration
- **Auto-Provisioning** - HTTPS/HTTP provisioning with authentication support
- **Line Assignment** - Assign FreePBX extensions to phone lines (up to 16 lines per phone)
- **BLF/Speed Dial** - Configure programmable line keys for BLF monitoring and speed dial
- **Network-Based Config** - Different settings per network (CIDR-based matching)
- **SIP NOTIFY** - Trigger phone reconfigurations remotely via Asterisk
- **Codec Management** - Configure codec priorities per network (G.711, G.722, G.729, Opus)

## Supported Models

- **T3x Series**: T31, T31G, T31P, T33G, T33P, T34W
- **T4x Series**: T42G, T42S, T42U, T46G, T46S, T46U, T48G, T48S, T48U
- **T5x Series**: T52S, T52W, T53, T53W, T54S, T54W, T56A, T57W, T58A, T58W

## Installation

From FreePBX Module admin, add a module with the url of https://github.com/jaysamthanki/yealinkphones/archive/refs/heads/main.zip

## Configuration

### 1. Add Network

Navigate to **Connectivity → Yealink Phones → Networks**

- Configure CIDR range (e.g., `192.168.1.0/24`)
- Set provisioning protocol (HTTP/HTTPS) and authentication
- Configure SIP server address and port
- Set codec priorities
- Configure NTP and timezone

### 2. Add Phone (Auto-Discovery)

**Option A: Automatic Registration (Recommended)**

Simply configure the phone to point to your provisioning server. When the phone contacts the server and authenticates successfully, it will automatically register itself in the database with:
- Auto-generated name: "Auto-Discovered T33 (MAC)"
- Model and firmware auto-detected from User-Agent
- Last IP and config time tracked

After auto-discovery, navigate to **Connectivity → Yealink Phones → Phones** to:
- Edit the phone name
- Assign FreePBX extensions to lines
- Configure BLF/speed dial buttons

For the automatic registration to work, add a DHCP Option 66 (text) to `https://<username>:<passwor>@yourpbxiporhostname/yealink`

The username / password would be setup in step #1 which is the network setup phase. The deafult is yealink/yealink. It's recommended to change this as bots will try and find a matching mac address if you dont.

**Option B: Manual Registration**

Navigate to **Connectivity → Yealink Phones → Phones → Add Phone**

- Enter phone name and MAC address (12 hex digits, no separators)
- Assign FreePBX extensions to lines
- Configure BLF/speed dial buttons

### 3. Configure Phone for Auto-Provisioning

**Option A: DHCP Option 66**
```
option tftp-server-name "https://your-freepbx-server/yealink";
```

**Option B: Manual Configuration**

On the phone:
1. Press **Menu** → **Settings** → **Advanced** (password: `admin`)
2. Go to **Auto Provision** → **Server URL**
3. Enter: `https://your-freepbx-server/yealink`
4. Set **Server Type** to **HTTP/HTTPS**
5. Enter username/password if authentication is enabled
6. Press **Auto Provision Now**

## Provisioning URL Structure

- **Boot File**: `https://your-server/yealink/boot.php?mac={MAC}`
- **Common CFG**: `https://your-server/yealink/common.php?model={XX}`
- **MAC CFG**: `https://your-server/yealink/config.php?mac={MAC}`

## Line Key Types

- **Type 15**: Line (SIP account line appearance)
- **Type 16**: BLF (Busy Lamp Field - monitor extension status)
- **Type 13**: Speed Dial (direct dial to number)
- **Type 11**: DTMF (send DTMF sequence)
- **Type 14**: Intercom (auto-answer intercom)
- **Type 10**: Call Park
- **Type 27**: Group Pickup

## Triggering Config Updates

Phones can be notified to reload configuration in two ways:

1. **Via GUI**: Click "Notify" button next to phone in phone list
2. **Via Asterisk CLI**:
   ```
   pjsip send notify yealink-check-cfg endpoint 1001
   ```

## Troubleshooting

### Phone not provisioning

1. Check phone can reach provisioning URL (test from web browser)
2. Verify MAC address is correct (uppercase, 12 hex digits, no separators)
3. Check network authentication settings match phone config
4. Review phone logs: **Menu → Status → Network → HTTP**

### Phone not registering

1. Verify SIP server address in network settings
2. Check FreePBX extension credentials
3. Verify NAT settings if phone is remote
4. Check Asterisk logs: `asterisk -rx "pjsip show registration 1001"`

### BLF not working

1. Ensure phone is registered
2. Verify extension to monitor exists
3. Check line key type is set to BLF (type 16)
4. Verify "Value" field contains the extension number

## File Locations

- **Module Code**: `/var/www/html/admin/modules/yealinkphones/`
- **Provisioning**: `/var/www/html/yealink/` (symlink)
- **Configs**: `/var/www/html/admin/modules/_yealink_software/configs/`
- **Logs**: `/var/www/html/admin/modules/_yealink_software/logs/`

## License

GNU General Public License v2.0 or later

## Credits

Created by Techie Networks Inc for FreePBX 17

Inspired by the Polycom Phones module by Excalibur Partners

