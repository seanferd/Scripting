# Zplug home
export ZPLUG_HOME="$HOME/.zplug"

if test ! $(which zplug)
then
  echo "  Installing zplug for you."

  curl -sL zplug.sh/installer | zsh
  source "$ZPLUG_HOME/init.zsh"
  zplug update --self
fi
