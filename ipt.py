import sys
import subprocess

def check_and_install_packages():
    required_packages = ['Flask', 'pycryptodome']
    print("正在检测所需的 Python 库...")

    packages_to_install = []
    
    for package in required_packages:
        try:
            if package == 'pycryptodome':
                __import__('Crypto')
            else:
                __import__(package)
            print(f"✅ {package} 牢布斯的你居然全部都有你是苦命程序员吧！")
        except ImportError:
            print(f"⚠️ {package} 牢布斯你怎么没有安装！")
            packages_to_install.append(package)

    if packages_to_install:
        print("\n牢布斯的你居然有没有安装的！")
        try:
            subprocess.check_call([sys.executable, "-m", "pip", "install"] + packages_to_install)
            print("\n🎉 牢布斯的我给你安装好了不用谢")
        except subprocess.CalledProcessError as e:
            print("\n❌ 自动安装失败。")
            print(f"牢布斯的我给你安装失败了！你自己用下面的安装吧！：")
            print(f"   pip install {' '.join(packages_to_install)}")
            sys.exit(1)

    print("\n-----------------------------------------------------")
    print("✅ 牢布斯的你居然全部都安装了！")
    print("快去运行app.py!")
    print("-----------------------------------------------------")

if __name__ == '__main__':
    check_and_install_packages()