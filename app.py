from flask import Flask, request, render_template, jsonify, send_file
import os
import struct
from Crypto.Cipher import AES
import base64
from Crypto.Random import get_random_bytes
import time
import webbrowser

app = Flask(__name__)

def encrypt_file_logic(file_path, password, save_path, new_filename=None):
    try:
        key = password.encode('utf-8').ljust(16)[:16]
        cipher = AES.new(key, AES.MODE_EAX)
        
        with open(file_path, 'rb') as f:
            plaintext = f.read()

        ciphertext, tag = cipher.encrypt_and_digest(plaintext)
        
        original_filename = os.path.basename(file_path)
        original_filename_bytes = original_filename.encode('utf-8')
        filename_len_bytes = struct.pack('!I', len(original_filename_bytes))
        
        if new_filename:
            base_name = os.path.splitext(new_filename)[0]
            final_filename = base_name + ".kz"
            encrypted_file_path = os.path.join(save_path, final_filename)
        else:
            encrypted_file_path = os.path.join(save_path, original_filename + ".kz")

        if os.path.exists(encrypted_file_path):
            return None, f"文件 '{os.path.basename(encrypted_file_path)}' 已存在，请先处理旧文件。"

        with open(encrypted_file_path, 'wb') as f:
            f.write(filename_len_bytes)
            f.write(original_filename_bytes)
            f.write(cipher.nonce)
            f.write(tag)
            f.write(ciphertext)

        return encrypted_file_path, "文件加密成功！"
    except Exception as e:
        return None, f"加密失败：{e}"

def decrypt_file_logic(file_path, password, save_path):
    try:
        key = password.encode('utf-8').ljust(16)[:16]
        
        with open(file_path, 'rb') as f:
            filename_len = struct.unpack('!I', f.read(4))[0]
            original_filename = f.read(filename_len).decode('utf-8')
            nonce, tag, ciphertext = f.read(16), f.read(16), f.read()

        cipher = AES.new(key, AES.MODE_EAX, nonce)
        plaintext = cipher.decrypt_and_verify(ciphertext, tag)
        
        decrypted_file_path = os.path.join(save_path, original_filename)

        if os.path.exists(decrypted_file_path):
            return None, f"文件 '{decrypted_file_path}' 已存在，为避免覆盖，请先处理旧文件。"

        with open(decrypted_file_path, 'wb') as f:
            f.write(plaintext)

        return decrypted_file_path, "文件解密成功！"
    except (ValueError, KeyError):
        return None, "解密失败：密码错误或文件已损坏。"
    except Exception as e:
        return None, f"解密失败：{e}"

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/process_file', methods=['POST'])
def process_file():
    file = request.files.get('file')
    password = request.form.get('password')
    action = request.form.get('action')
    new_filename = request.form.get('new_filename', None)

    if not file or not password:
        return jsonify({'success': False, 'message': '请选择文件并输入密码'}), 400

    temp_path = os.path.join(os.getcwd(), file.filename)
    file.save(temp_path)
    
    time.sleep(1) 

    if action == 'encrypt':
        result_path, message = encrypt_file_logic(temp_path, password, os.getcwd(), new_filename)
    elif action == 'decrypt':
        result_path, message = decrypt_file_logic(temp_path, password, os.getcwd())
    else:
        os.remove(temp_path)
        return jsonify({'success': False, 'message': '未知操作类型'}), 400
    
    os.remove(temp_path)

    if result_path:
        return jsonify({
            'success': True,
            'message': message,
            'download_url': f'/download/{os.path.basename(result_path)}'
        })
    else:
        return jsonify({'success': False, 'message': message})

@app.route('/download/<filename>')
def download_file(filename):
    file_path = os.path.join(os.getcwd(), filename)
    if os.path.exists(file_path):
        return send_file(file_path, as_attachment=True)
    else:
        return "文件不存在", 404
        
@app.route('/generate_key', methods=['POST'])
def generate_key():
    try:
        key = base64.b64encode(get_random_bytes(16)).decode('utf-8')
        return jsonify({'success': True, 'key': key})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})

if __name__ == '__main__':
    port = 5000
    url = f"http://127.0.0.1:{port}"
    time.sleep(1)
    webbrowser.open(url)
    app.run(port=port, debug=True)