import os
import time
import json
import requests
from datetime import datetime, timezone, timedelta
from jwcrypto import jwk, jws, jwe

BASE_URL = "https://nscra.ir/api/1.0/external"
LAST_PAYLOAD_FILE = "last_payload.json"

C_GREEN = "\033[92m"
C_YELLOW = "\033[93m"
C_RED = "\033[91m"
C_BLUE = "\033[94m"
C_CYAN = "\033[96m"
C_RESET = "\033[0m"
C_BOLD = "\033[1m"

class NSCRACrypto:
    def __init__(self, client_id, client_private_key_pem, server_public_key_pem):
        self.client_id = client_id
        self.private_key = client_private_key_pem
        self.server_public_key = server_public_key_pem

    @staticmethod
    def get_tehran_epoch():
        tehran_tz = timezone(timedelta(hours=3, minutes=30))
        return int(datetime.now(tehran_tz).timestamp())

    def encrypt_and_sign(self, data_dict, path):
        current_iat = self.get_tehran_epoch()
        client_key = jwk.JWK.from_pem(self.private_key.encode('utf-8'))
        
        jws_payload = {
            "path": path,
            "data": json.dumps(data_dict, separators=(',', ':')),
            "method": "POST",
            "iat": current_iat
        }
        
        jws_token = jws.JWS(json.dumps(jws_payload, separators=(',', ':')).encode('utf-8'))
        jws_token.add_signature(
            client_key,
            protected={"alg": "ES256", "clientID": self.client_id}
        )
        signed_jws = jws_token.serialize(compact=True)
        
        server_key = jwk.JWK.from_pem(self.server_public_key.encode('utf-8'))
        jwe_payload = {
            "data": signed_jws,
            "iat": current_iat
        }
        
        jwe_token = jwe.JWE(
            plaintext=json.dumps(jwe_payload, separators=(',', ':')).encode('utf-8'),
            protected={"alg": "ECDH-ES+A256KW", "enc": "A256GCM"},
            recipient=server_key
        )
        return jwe_token.serialize(compact=True)

    def decrypt_and_verify(self, encrypted_response_body):
        current_time = self.get_tehran_epoch()
        client_key = jwk.JWK.from_pem(self.private_key.encode('utf-8'))
        
        jwe_token = jwe.JWE()
        jwe_token.deserialize(encrypted_response_body)
        jwe_token.decrypt(client_key)
        
        jwe_payload = json.loads(jwe_token.payload.decode('utf-8'))
        if abs(current_time - jwe_payload.get('iat', 0)) > 300:
            raise ValueError("JWE timestamp expired.")
            
        inner_jws = jwe_payload['data']
        server_key = jwk.JWK.from_pem(self.server_public_key.encode('utf-8'))
        
        jws_token = jws.JWS()
        jws_token.deserialize(inner_jws)
        jws_token.verify(server_key)
        
        jws_payload = json.loads(jws_token.payload.decode('utf-8'))
        if abs(current_time - jws_payload.get('iat', 0)) > 300:
            raise ValueError("JWS signature timestamp expired.")
        
        return json.loads(jws_payload['data'])

def load_or_create_config():
    config_file = "config.json"
    if os.path.exists(config_file):
        with open(config_file, "r", encoding="utf-8") as f:
            return json.load(f)
    else:
        config = {
            "client_id": "test",
            "provider_code": "1234",
            "api_key": "test"
        }
        with open(config_file, "w", encoding="utf-8") as f:
            json.dump(config, f, indent=4, ensure_ascii=False)
        return config

def save_config(config):
    with open("config.json", "w", encoding="utf-8") as f:
        json.dump(config, f, indent=4, ensure_ascii=False)

def generate_request_id(provider_code):
    tehran_tz = timezone(timedelta(hours=3, minutes=30))
    now = datetime.now(tehran_tz)
    time_str = now.strftime("%Y%m%d%H%M%S%f")
    return f"{provider_code}{time_str}"

def load_or_create_keys():
    priv_file = "private_key.pem"
    pub_file = "public_key.pem"
    
    if os.path.exists(priv_file) and os.path.exists(pub_file):
        with open(priv_file, "r", encoding="utf-8") as f:
            private_pem = f.read()
        with open(pub_file, "r", encoding="utf-8") as f:
            public_pem = f.read()
        return private_pem, public_pem, False
    else:
        key = jwk.JWK.generate(kty='EC', crv='P-256')
        private_pem = key.export_to_pem(private_key=True, password=None).decode('utf-8')
        public_pem = key.export_to_pem(private_key=False).decode('utf-8')
        
        with open(priv_file, "w", encoding="utf-8") as f:
            f.write(private_pem)
        with open(pub_file, "w", encoding="utf-8") as f:
            f.write(public_pem)
        return private_pem, public_pem, True

def get_server_public_key():
    with open("PublicKey.pem", "r", encoding="utf-8") as f:
        return f.read()

def register_public_key(client_id, public_key_pem, api_key, otp=""):
    url = f"{BASE_URL}/dc/register-key"
    payload = {
        "clientId": client_id,
        "publicKey": public_key_pem,
        "otp": otp
    }
    headers = {
        "Content-Type": "application/json",
        "X-API-KEY": api_key
    }
    response = requests.post(url, json=payload, headers=headers)
    return response.json()

def send_encrypted_request(encrypted_payload, api_key, endpoint):
    url = f"{BASE_URL}{endpoint}"
    payload = {
        "signedEncryptedPayload": encrypted_payload
    }
    headers = {
        "Content-Type": "application/json",
        "X-API-KEY": api_key
    }
    response = requests.post(url, json=payload, headers=headers)
    return response.json()

def check_request_status(tracking_id, crypto_manager, api_key):
    url = f"{BASE_URL}/dc/status/{tracking_id}"
    headers = {
        "X-API-KEY": api_key
    }
    response = requests.get(url, headers=headers)
    res_json = response.json()
    
    data_block = res_json.get("data", {})
    if isinstance(data_block, dict):
        encrypted_body = data_block.get("responseBody")
        if encrypted_body:
            try:
                decrypted_body = crypto_manager.decrypt_and_verify(encrypted_body)
                data_block["responseBodyDecrypted"] = decrypted_body
            except Exception as e:
                data_block["decryption_error"] = str(e)
            
    return res_json

def get_service_template(action, customer_type, service_type):
    base_address = {
        "provinceCode": "021",
        "townshipName": "تهران",
        "address": "خیابان آزادی کوچه شهید احمد علی نیری پلاک 18",
        "street2": "کوچه شهید احمد علی نیری",
        "houseNumber": "18",
        "postalCode": "1345676543",
        "tel": "02122334455"
    }

    if action == "put":
        if customer_type == "real":
            payload = {
                "identificationType": 0,
                "identificationNo": "0987654321",
                "address": base_address
            }
        else:
            payload = {
                "identificationType": 5,
                "identificationNo": "33273340437",
                "mobileNumber": "09128964532",
                "agentIdentificationType": 0,
                "agentIdentificationNo": "0072314567",
                "address": base_address
            }

        if service_type == "shared":
            payload["service"] = {
                "type": 35,
                "dataCenterId": "34689999",
                "dataCenterType": 14,
                "ips": "185.168.12.10-185.168.12.10",
                "bandwidth": 256,
                "startDate": "14050101",
                "endDate": "14051229",
                "hasSSL": 1,
                "hasIXP": 1,
                "urlList": "cra.ir"
            }
        elif service_type == "vps":
            payload["service"] = {
                "type": 35,
                "dataCenterId": "34689999",
                "dataCenterType": 11,
                "ips": "185.168.12.10-185.168.12.10",
                "bandwidth": 256,
                "startDate": "14050101",
                "endDate": "14051229",
                "hasIXP": 1,
                "urlList": "cra.ir",
                "centerName": "شقایق",
                "province": "021",
                "dataCenterAddress": "تهران، خیابان شریعتی ورودی شماره ۱۷ وزارت ارتباطات"
            }
        elif service_type in ["dedicated", "colocation"]:
            payload["service"] = {
                "type": 35,
                "dataCenterId": "34689999",
                "dataCenterType": 12 if service_type == "dedicated" else 13,
                "ips": "185.168.12.10-185.168.12.10",
                "bandwidth": 256,
                "startDate": "14050101",
                "endDate": "14051229",
                "hasIXP": 1,
                "centerName": "شقایق",
                "province": "021",
                "dataCenterAddress": "تهران، خیابان شریعتی ورودی شماره ۱۷ وزارت ارتباطات",
                "lat": "35.689198",
                "lon": "51.388973",
                "rowIndex": 1,
                "racIndex": 1,
                "unitIndex": 1,
                "units": 4
            }
        elif service_type == "cdn":
            payload["service"] = {
                "type": 35,
                "dataCenterId": "34689999",
                "dataCenterType": 15,
                "ips": "185.168.12.10-185.168.12.10",
                "bandwidth": 256,
                "startDate": "14050101",
                "endDate": "14051229",
                "hasSSL": 1,
                "urlList": "cra.ir"
            }

    elif action == "update":
        payload = {
            "id": "WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8",
            "serviceNumber": "34689658"
        }
        
        if customer_type == "legal":
            payload["customerUpdate"] = {
                "agentIdentificationType": 0,
                "agentIdentificationNo": "0063222313"
            }
            
        payload["addressUpdate"] = {
            "townshipName": "فیروزکوه",
            "address": "خیابان امام کوچه شهید مهدوی پلاک 8 طبقه 1 واحد 2",
            "street2": "کوچه شهید مهدوی",
            "houseNumber": "8",
            "postalCode": "7654316543",
            "tel": "02178334455"
        }

        if service_type == "shared":
            payload["serviceUpdate"] = {
                "hasIXP": 1,
                "ips": "185.168.12.11-185.168.12.11"
            }
        elif service_type == "vps":
            payload["serviceUpdate"] = {
                "bandwidth": 512,
                "ips": "185.168.12.11-185.168.12.11"
            }
        elif service_type in ["dedicated", "colocation"]:
            payload["serviceUpdate"] = {
                "hasIXP": 1,
                "rowIndex": 10,
                "racIndex": 3,
                "unitIndex": 12,
                "ips": "185.168.12.11-185.168.12.11"
            }
        elif service_type == "cdn":
            payload["serviceUpdate"] = {
                "hasSSL": 1,
                "bandwidth": 128,
                "ips": "185.168.12.11-185.168.12.11"
            }

    elif action == "close":
        payload = {
            "id": "tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM"
        }

    return payload

def print_banner(config):
    os.system('cls' if os.name == 'nt' else 'clear')
    print(f"{C_CYAN}{C_BOLD}=================================================================={C_RESET}")
    print(f"{C_CYAN}{C_BOLD}     سامانه ثبت اطلاعات دیتاسنتری سازمان تنظیم مقررات (NSCRA)     {C_RESET}")
    print(f"{C_CYAN}{C_BOLD}=================================================================={C_RESET}")
    print(f"{C_BLUE}[i] مقدار client_id: {C_BOLD}{config['client_id']}{C_RESET} | {C_BLUE}مقدار provider_code: {C_BOLD}{config['provider_code']}{C_RESET}")
    print(f"{C_BLUE}[*] مقدار X-API-KEY فعال: {C_BOLD}{config['api_key'][:10]}...{C_RESET}")
    print(f"{C_CYAN}=================================================================={C_RESET}")

def select_options():
    print(f"\n{C_BOLD}نوع عملیات را انتخاب کنید:{C_RESET}")
    print(" 1. ثبت سرویس جدید (Put)")
    print(" 2. به‌روزرسانی سرویس موجود (Update)")
    print(" 3. حذف سرویس")
    act_ch = input("انتخاب کنید (۱-۳): ").strip()
    action = "put" if act_ch == "1" else "update" if act_ch == "2" else "close" if act_ch == "3" else None
    
    if not action:
        return None, None, None, None, None

    if action == "close":
        return action, None, None, "/rest/shahkar/datacenter/delete", "/dc/delete"

    path = "/rest/shahkar/datacenter/put" if action == "put" else "/rest/shahkar/datacenter/update"
    endpoint = "/dc/send" if action == "put" else "/dc/update"

    print(f"\n{C_BOLD}نوع مشتری را انتخاب کنید:{C_RESET}")
    print(" 1. شخص حقیقی")
    print(" 2. شخص حقوقی")
    cust_ch = input("انتخاب کنید (۱-۲): ").strip()
    customer_type = "real" if cust_ch == "1" else "legal" if cust_ch == "2" else None
    
    if not customer_type:
        return None, None, None, None, None

    print(f"\n{C_BOLD}نوع سرویس مرکز داده را انتخاب کنید:{C_RESET}")
    print(" 1. Shared WebHosting")
    print(" 2. VPS")
    print(" 3. Dedicated Server")
    print(" 4. Colocation")
    print(" 5. CDN")
    srv_ch = input("انتخاب کنید (۱-۵): ").strip()
    service_type = (
        "shared" if srv_ch == "1" else
        "vps" if srv_ch == "2" else
        "dedicated" if srv_ch == "3" else
        "colocation" if srv_ch == "4" else
        "cdn" if srv_ch == "5" else None
    )
    
    if not service_type:
        return None, None, None, None, None

    return action, customer_type, service_type, path, endpoint

def read_multiline_json(req_id):
    print(f"{C_YELLOW}JSON دلخواه خود را پیست (Paste) کنید.")
    print("برنامه به طور خودکار کامل شدن ساختار JSON را تشخیص داده و ادامه می‌دهد.")
    print(f"اگر خطای ساختاری وجود داشت و برنامه متوقف ماند، با فشردن {C_BOLD}Enter روی یک خط خالی{C_RESET}{C_YELLOW}، خروج اضطراری کنید:{C_RESET}\n")
    
    lines = []
    while True:
        try:
            line = input()
            lines.append(line)
            full_text = "\n".join(lines).strip()
            
            if not line.strip() and len(lines) > 1:
                try:
                    payload_candidate = "\n".join(lines[:-1]).strip()
                    payload = json.loads(payload_candidate)
                    payload["requestId"] = req_id
                    print(f"\n{C_GREEN}[✓] پردازش اطلاعات پیش از خروج دستی با موفقیت انجام شد.{C_RESET}")
                    return payload
                except Exception as e:
                    print(f"\n{C_RED}[!] خطای اعتبارسنجی JSON: {e}{C_RESET}")
                    print(f"{C_RED}از اطلاعات پیش‌فرض الگو استفاده خواهد شد.{C_RESET}")
                    time.sleep(3)
                    return None
            
            # تلاش برای پارس خودکار لحظه‌ای
            if full_text:
                try:
                    payload = json.loads(full_text)
                    payload["requestId"] = req_id
                    print(f"\n{C_GREEN}[✓] ساختار معتبر JSON به صورت خودکار تشخیص داده و پارس شد.{C_RESET}")
                    return payload
                except json.JSONDecodeError:
                    pass
        except EOFError:
            break
            
    return None

def main():
    try:
        server_pub_key = get_server_public_key()
    except FileNotFoundError:
        print(f"\n{C_RED}[!] خطا: فایل کلید عمومی سرور ({C_BOLD}PublicKey.pem{C_RESET}{C_RED}) پیدا نشد.{C_RESET}\n")
        return

    config = load_or_create_config()
    client_priv, client_pub, is_new = load_or_create_keys()
    crypto = NSCRACrypto(config["client_id"], client_priv, server_pub_key)

    while True:
        print_banner(config)
        if is_new:
            print(f"{C_GREEN}[+] جفت کلید جدید اختصاصی شما ساخته و ذخیره شد.{C_RESET}\n")
            is_new = False
            
        print("منوی اصلی:")
        print(f" {C_BOLD}1.{C_RESET} تغییر تنظیمات پیکربندی (client_id / provider_code / api_key)")
        print(f" {C_BOLD}2.{C_RESET} ثبت کلید عمومی - مرحله اول (دریافت OTP)")
        print(f" {C_BOLD}3.{C_RESET} ثبت کلید عمومی - مرحله دوم ({C_RED}ارسال اجباری OTP{C_RESET})")
        print(f" {C_BOLD}4.{C_RESET} ارسال درخواست سرویس - مرحله اول (دریافت OTP از فرم‌های آماده)")
        print(f" {C_BOLD}5.{C_RESET} ارسال درخواست سرویس - مرحله دوم ({C_RED}ادغام خودکار و ارسال OTP{C_RESET})")
        print(f" {C_BOLD}6.{C_RESET} استعلام وضعیت درخواست و بازگشایی خودکار")
        print(f" {C_BOLD}7.{C_RESET} خروج")
        
        choice = input(f"\n{C_BOLD}انتخاب کنید (۱-۷): {C_RESET}")
        
        if choice == "1":
            print_banner(config)
            new_client = input(f"مقدار client_id جدید را وارد کنید [{config['client_id']}]: ").strip()
            new_provider = input(f"مقدار provider_code جدید را وارد کنید [{config['provider_code']}]: ").strip()
            new_api_key = input(f"مقدار api_key جدید (هدر X-API-KEY) را وارد کنید [{config['api_key']}]: ").strip()
            if new_client:
                config["client_id"] = new_client
            if new_provider:
                config["provider_code"] = new_provider
            if new_api_key:
                config["api_key"] = new_api_key
            save_config(config)
            crypto.client_id = config["client_id"]
            print(f"\n{C_GREEN}[✓] تنظیمات با موفقیت ذخیره شدند.{C_RESET}")
            time.sleep(1.5)

        elif choice == "2":
            print_banner(config)
            print(f"\n{C_YELLOW}[→] ارسال کلید عمومی برای دریافت پیامک تایید...{C_RESET}")
            res1 = register_public_key(config["client_id"], client_pub, config["api_key"])
            print(f"\n{C_GREEN}[✓] پاسخ مرحله اول سرور:{C_RESET}")
            print(json.dumps(res1, indent=2, ensure_ascii=False))
            input(f"\n{C_BLUE}برای بازگشت کلید Enter را فشار دهید...{C_RESET}")

        elif choice == "3":
            print_banner(config)
            otp_code = input(f"{C_BOLD}{C_YELLOW}[!] کد تایید پیامک شده (OTP) برای ثبت کلید را وارد کنید: {C_RESET}")
            if not otp_code.strip():
                print(f"{C_RED}کد تایید نمی‌تواند خالی باشد.{C_RESET}")
                time.sleep(1.5)
                continue
            print(f"\n{C_YELLOW}[→] ارسال مرحله دوم ثبت کلید با کد تایید...{C_RESET}")
            res2 = register_public_key(config["client_id"], client_pub, config["api_key"], otp=otp_code.strip())
            print(f"\n{C_GREEN}[✓] پاسخ نهایی ثبت کلید:{C_RESET}")
            print(json.dumps(res2, indent=2, ensure_ascii=False))
            input(f"\n{C_BLUE}برای بازگشت کلید Enter را فشار دهید...{C_RESET}")

        elif choice == "4":
            print_banner(config)
            action, customer_type, service_type, path, endpoint = select_options()
            if not action:
                print(f"{C_RED}ورودی نامعتبر.{C_RESET}")
                time.sleep(1.5)
                continue
            
            print_banner(config)
            payload = get_service_template(action, customer_type, service_type)
            req_id = generate_request_id(config["provider_code"])
            payload["requestId"] = req_id
            
            print(f"{C_GREEN}[✓] الگوی درخواستی هماهنگ با مستندات به شرح زیر بارگذاری شد:{C_RESET}")
            print(json.dumps(payload, indent=2, ensure_ascii=False))
            
            edit = input("\nآیا تمایل به تغییر اطلاعات یا ارسال دیتای کاستوم خود دارید؟ (y/n): ")
            if edit.lower() == 'y':
                custom_payload = read_multiline_json(req_id)
                if custom_payload:
                    payload = custom_payload
            
            print(f"\n{C_YELLOW}[→] رمزگذاری اطلاعات و ارسال درخواست اولیه...{C_RESET}")
            encrypted_data = crypto.encrypt_and_sign(payload, path)
            response = send_encrypted_request(encrypted_data, config["api_key"], endpoint)
            
            meta_data = {
                "payload": payload,
                "customer_type": customer_type,
                "path": path,
                "endpoint": endpoint
            }
            with open(LAST_PAYLOAD_FILE, "w", encoding="utf-8") as f:
                json.dump(meta_data, f, indent=4, ensure_ascii=False)
                
            print(f"\n{C_GREEN}[✓] پاسخ اولیه سرور (دریافت و صف):{C_RESET}")
            print(json.dumps(response, indent=2, ensure_ascii=False))
            print(f"\n{C_BLUE}[i] اطلاعات درخواست موقتاً جهت ادغام اتوماتیک با OTP ذخیره شد.{C_RESET}")
            input(f"\n{C_BLUE}برای بازگشت کلید Enter را فشار دهید...{C_RESET}")

        elif choice == "5":
            print_banner(config)
            if not os.path.exists(LAST_PAYLOAD_FILE):
                print(f"{C_RED}[!] خطا: اطلاعات درخواست قبلی یافت نشد. ابتدا مرحله اول (گزینه ۴) را انجام دهید.{C_RESET}")
                time.sleep(2)
                continue
                
            with open(LAST_PAYLOAD_FILE, "r", encoding="utf-8") as f:
                meta_data = json.load(f)
                
            payload = meta_data["payload"]
            customer_type = meta_data["customer_type"]
            path = meta_data["path"]
            endpoint = meta_data.get("endpoint", "/dc/send")
                
            print(f"{C_BLUE}[i] درخواست معلق یافت شده با شناسه: {C_BOLD}{payload.get('requestId')}{C_RESET}")
            
            if customer_type == "legal":
                otp_val = input(f"{C_BOLD}{C_YELLOW}کد تایید پیامکی سرویس‌گیرنده (otp) را وارد کنید: {C_RESET}")
                agent_otp_val = input(f"{C_BOLD}{C_YELLOW}کد تایید پیامکی نماینده سرویس‌گیرنده (agentOtp) را وارد کنید: {C_RESET}")
                
                if not otp_val.strip() or not agent_otp_val.strip():
                    print(f"{C_RED}کدها نمی‌توانند خالی باشند.{C_RESET}")
                    time.sleep(1.5)
                    continue
                payload["otp"] = int(otp_val.strip())
                payload["agentOtp"] = int(agent_otp_val.strip())
            else:
                otp_val = input(f"{C_BOLD}{C_YELLOW}کد تایید پیامکی (otp) را وارد کنید: {C_RESET}")
                if not otp_val.strip():
                    print(f"{C_RED}کد تایید نمی‌تواند خالی باشد.{C_RESET}")
                    time.sleep(1.5)
                    continue
                payload["otp"] = int(otp_val.strip())
                
            print(f"\n{C_YELLOW}[→] در حال ادغام اتوماتیک فیلدهای OTP با دیتای قبل و ارسال رمزنگاری شده مجدد...{C_RESET}")
            encrypted_data = crypto.encrypt_and_sign(payload, path)
            response = send_encrypted_request(encrypted_data, config["api_key"], endpoint)
            
            print(f"\n{C_GREEN}[✓] پاسخ نهایی ثبت درخواست سرویس:{C_RESET}")
            print(json.dumps(response, indent=2, ensure_ascii=False))
            input(f"\n{C_BLUE}برای بازگشت کلید Enter را فشار دهید...{C_RESET}")

        elif choice == "6":
            print_banner(config)
            tracking_id = input(f"{C_BOLD}شماره پیگیری (trackingId) را جهت استعلام وارد کنید: {C_RESET}")
            if tracking_id.strip():
                print(f"\n{C_YELLOW}[→] دریافت وضعیت و رمزگشایی خودکار فیلد responseBody...{C_RESET}")
                result = check_request_status(tracking_id.strip(), crypto, config["api_key"])
                print(f"\n{C_GREEN}[✓] نتیجه استعلام رمزگشایی شده:{C_RESET}")
                print(json.dumps(result, indent=2, ensure_ascii=False))
            input(f"\n{C_BLUE}برای بازگشت کلید Enter را فشار دهید...{C_RESET}")

        elif choice == "7":
            break

if __name__ == "__main__":
    main()